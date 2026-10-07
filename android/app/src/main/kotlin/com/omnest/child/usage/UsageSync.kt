package com.omnest.child.usage

import android.content.Context
import androidx.core.content.edit
import com.omnest.child.data.DeviceSession
import com.omnest.child.data.api.AppSecondsDto
import com.omnest.child.data.api.AppsUpload
import com.omnest.child.data.api.OmnestApi
import com.omnest.child.data.api.UsageDayDto
import com.omnest.child.data.api.UsageUpload
import com.omnest.child.data.db.UsageDao
import com.omnest.child.permissions.Requirement
import dagger.hilt.android.qualifiers.ApplicationContext
import retrofit2.Response
import java.io.IOException
import java.time.LocalDate
import java.time.ZoneId
import javax.inject.Inject
import javax.inject.Singleton

enum class SyncResult { Done, NotReady, RetryLater, Unpaired }

/**
 * One sync pass:
 *  1. recompute today + yesterday (the last 7 days on first run) into Room
 *  2. upload the app list if it changed
 *  3. upload days whose numbers changed since the last accepted upload
 * Everything is kept locally first, so nothing is lost while offline.
 */
@Singleton
class UsageSync @Inject constructor(
    @ApplicationContext private val context: Context,
    private val reader: UsageReader,
    private val inventory: AppInventory,
    private val dao: UsageDao,
    private val api: OmnestApi,
    private val session: DeviceSession,
) {
    private val prefs = context.getSharedPreferences("omnest_sync", Context.MODE_PRIVATE)

    suspend fun run(zone: ZoneId = ZoneId.systemDefault()): SyncResult {
        val auth = session.authHeader ?: return SyncResult.NotReady
        if (!Requirement.UsageAccess.isGranted(context)) return SyncResult.NotReady

        computeLocal(zone)

        val apps = try { uploadAppsIfChanged(auth) } catch (_: IOException) { return SyncResult.RetryLater }
        if (apps == SyncResult.Unpaired) return unpaired()

        val usage = try { uploadPendingDays(auth, zone) } catch (_: IOException) { return SyncResult.RetryLater }
        return if (usage == SyncResult.Unpaired) unpaired() else usage
    }

    private suspend fun computeLocal(zone: ZoneId) {
        val today = LocalDate.now(zone)
        val backfill = if (dao.dayCount() == 0) FIRST_RUN_DAYS else 2
        val now = System.currentTimeMillis()
        for (i in 0 until backfill) {
            val date = today.minusDays(i.toLong())
            dao.replaceDay(date.toString(), reader.secondsFor(date, zone, now), now)
        }
        val cutoff = today.minusDays(KEEP_LOCAL_DAYS).toString()
        dao.purgeUsage(cutoff)
        dao.purgeSync(cutoff)
    }

    private suspend fun uploadAppsIfChanged(auth: String): SyncResult {
        val apps = inventory.launchableApps()
        val fingerprint = inventory.fingerprint(apps)
        if (fingerprint == prefs.getString(KEY_APPS_FINGERPRINT, null)) return SyncResult.Done

        val res = api.syncApps(auth, AppsUpload(apps.take(MAX_APPS)))
        return when {
            res.isSuccessful -> {
                prefs.edit { putString(KEY_APPS_FINGERPRINT, fingerprint) }
                SyncResult.Done
            }
            res.code() == 401 -> SyncResult.Unpaired
            else -> SyncResult.RetryLater
        }
    }

    private suspend fun uploadPendingDays(auth: String, zone: ZoneId): SyncResult {
        val pending = dao.pending(MAX_DAYS_PER_UPLOAD)
        if (pending.isEmpty()) return SyncResult.Done

        val days = pending.map { day ->
            UsageDayDto(
                date = day.date,
                apps = dao.day(day.date).take(MAX_APPS_PER_DAY).map { AppSecondsDto(it.packageName, it.seconds) },
            )
        }
        val startedAt = System.currentTimeMillis()
        val res: Response<*> = api.uploadUsage(auth, UsageUpload(zone.id, days))
        return when {
            res.isSuccessful -> {
                dao.markSynced(pending.map { it.date }, startedAt)
                SyncResult.Done
            }
            res.code() == 401 -> SyncResult.Unpaired
            // 422 means our data is wrong, not the network: don't retry forever, recompute next time.
            res.code() == 422 -> SyncResult.Done
            else -> SyncResult.RetryLater
        }
    }

    private fun unpaired(): SyncResult {
        // The parent removed this phone: forget the token so the app returns to the welcome screen.
        session.clear()
        prefs.edit { clear() }
        return SyncResult.Unpaired
    }

    private companion object {
        const val KEY_APPS_FINGERPRINT = "apps_fingerprint"
        const val FIRST_RUN_DAYS = 7
        const val KEEP_LOCAL_DAYS = 30L
        const val MAX_DAYS_PER_UPLOAD = 8
        const val MAX_APPS_PER_DAY = 500
        const val MAX_APPS = 1000
    }
}
