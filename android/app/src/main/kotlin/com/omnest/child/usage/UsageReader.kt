package com.omnest.child.usage

import android.app.usage.UsageEvents
import android.app.usage.UsageStatsManager
import android.content.Context
import android.content.Intent
import android.content.pm.PackageManager
import android.os.Build
import dagger.hilt.android.qualifiers.ApplicationContext
import java.time.LocalDate
import java.time.ZoneId
import javax.inject.Inject
import javax.inject.Singleton

/** Reads Android's UsageStats events and turns them into per-app seconds for a local day. */
@Singleton
class UsageReader @Inject constructor(@ApplicationContext private val context: Context) {

    private val usm = context.getSystemService(UsageStatsManager::class.java)

    /**
     * Foreground seconds per app on [date] in [zone], up to now for today.
     * Reads from 3 hours before midnight so sessions that started the evening before are clipped, not lost.
     */
    fun secondsFor(date: LocalDate, zone: ZoneId = ZoneId.systemDefault(), now: Long = System.currentTimeMillis()): Map<String, Int> {
        val from = date.atStartOfDay(zone).toInstant().toEpochMilli()
        val to = minOf(date.plusDays(1).atStartOfDay(zone).toInstant().toEpochMilli(), now)
        if (to <= from) return emptyMap()

        val events = readEvents(from - LOOKBACK_MILLIS, to)
        return UsageCalculator.toSeconds(UsageCalculator.foregroundMillis(events, from, to, excludedPackages()))
    }

    private fun readEvents(from: Long, to: Long): List<UsageEvent> {
        val raw = usm.queryEvents(from, to) ?: return emptyList()
        val out = ArrayList<UsageEvent>(1024)
        val e = UsageEvents.Event()
        while (raw.hasNextEvent()) {
            raw.getNextEvent(e)
            val type = when (e.eventType) {
                UsageEvents.Event.ACTIVITY_RESUMED -> UsageEvent.Type.Resumed
                UsageEvents.Event.ACTIVITY_PAUSED -> UsageEvent.Type.Paused
                else -> if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.P) {
                    when (e.eventType) {
                        UsageEvents.Event.SCREEN_NON_INTERACTIVE -> UsageEvent.Type.ScreenOff
                        UsageEvents.Event.DEVICE_SHUTDOWN -> UsageEvent.Type.Shutdown
                        else -> null
                    }
                } else {
                    null
                }
            } ?: continue
            out += UsageEvent(e.packageName.orEmpty(), type, e.timeStamp)
        }
        return out
    }

    /** Omnest itself, home screens and the system UI aren't "screen time". */
    private fun excludedPackages(): Set<String> {
        val home = Intent(Intent.ACTION_MAIN).addCategory(Intent.CATEGORY_HOME)
        val launchers = context.packageManager
            .queryIntentActivities(home, PackageManager.MATCH_DEFAULT_ONLY)
            .map { it.activityInfo.packageName }
        return launchers.toSet() + context.packageName + "com.android.systemui"
    }

    private companion object {
        const val LOOKBACK_MILLIS = 3 * 60 * 60 * 1000L
    }
}
