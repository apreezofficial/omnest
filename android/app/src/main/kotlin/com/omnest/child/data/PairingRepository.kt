package com.omnest.child.data

import android.content.Context
import android.os.Build
import com.omnest.child.usage.UsageSyncWorker
import dagger.hilt.android.qualifiers.ApplicationContext
import com.omnest.child.BuildConfig
import com.omnest.child.data.api.OmnestApi
import com.omnest.child.data.api.PairRequest
import com.omnest.child.push.PushTokens
import kotlinx.serialization.json.Json
import java.io.IOException
import javax.inject.Inject
import javax.inject.Singleton

sealed interface PairOutcome {
    data class Paired(val device: PairedDevice) : PairOutcome
    data class Rejected(val message: String) : PairOutcome
    data object Offline : PairOutcome
    data class Failed(val message: String) : PairOutcome
}

@Singleton
class PairingRepository @Inject constructor(
    @ApplicationContext private val context: Context,
    private val api: OmnestApi,
    private val session: DeviceSession,
    private val pushTokens: PushTokens,
    private val json: Json,
) {
    suspend fun pair(code: String): PairOutcome {
        val request = PairRequest(
            code = code,
            name = deviceName(),
            model = Build.MODEL?.take(80),
            osVersion = Build.VERSION.RELEASE?.take(20),
            appVersion = BuildConfig.VERSION_NAME,
            fcmToken = pushTokens.currentOrNull(),
        )

        val response = try {
            api.pair(request)
        } catch (_: IOException) {
            return PairOutcome.Offline
        }

        val body = response.body()
        if (response.isSuccessful && body != null) {
            val result = body.data
            val device = PairedDevice(
                token = result.token,
                deviceId = result.device.id,
                childId = result.child.id,
                childName = result.child.name,
                ageTier = result.child.ageTier,
            )
            session.save(device)
            UsageSyncWorker.schedule(context)
            UsageSyncWorker.runNow(context)
            return PairOutcome.Paired(device)
        }

        val error = response.errorBody().toApiError(json)
        return when (response.code()) {
            422 -> PairOutcome.Rejected(error?.fieldError("code") ?: error?.message ?: "That code didn't work.")
            429 -> PairOutcome.Rejected("Too many tries. Wait a few minutes, then try again.")
            else -> PairOutcome.Failed(error?.message ?: "Something went wrong. Try again in a moment.")
        }
    }

    /** "TECNO Spark 10" style name the parent will recognise. */
    private fun deviceName(): String {
        val maker = Build.MANUFACTURER.orEmpty().trim()
        val model = Build.MODEL.orEmpty().trim()
        val name = if (model.startsWith(maker, ignoreCase = true)) model else "$maker $model"
        return name.trim().ifEmpty { "Android phone" }.take(80)
    }
}
