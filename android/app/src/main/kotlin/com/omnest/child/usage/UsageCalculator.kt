package com.omnest.child.usage

/** Platform-free copy of the UsageStats events we care about (so the maths is unit-testable). */
data class UsageEvent(val packageName: String, val type: Type, val timeMillis: Long) {
    enum class Type {
        /** An activity of the app came to the front. */
        Resumed,

        /** An activity of the app left the front. */
        Paused,

        /** Screen turned off or locked: whatever was in front stops counting. */
        ScreenOff,

        /** Phone shutting down. */
        Shutdown,
    }
}

object UsageCalculator {

    /**
     * Foreground time per app inside [from, to), in milliseconds.
     *
     * Walks events in time order keeping one "app in front" session. Sessions that started
     * before [from] are clipped (so midnight splits correctly); one still open at the end runs to [to].
     */
    fun foregroundMillis(
        events: List<UsageEvent>,
        from: Long,
        to: Long,
        exclude: Set<String> = emptySet(),
    ): Map<String, Long> {
        val totals = HashMap<String, Long>()
        var current: String? = null
        var startedAt = 0L

        fun close(at: Long) {
            val pkg = current ?: return
            val start = maxOf(startedAt, from)
            val end = minOf(at, to)
            if (end > start && pkg !in exclude) {
                totals[pkg] = (totals[pkg] ?: 0L) + (end - start)
            }
            current = null
        }

        for (event in events.sortedBy { it.timeMillis }) {
            if (event.timeMillis >= to) break
            when (event.type) {
                UsageEvent.Type.Resumed -> {
                    // Another activity of the same app: the session just continues.
                    if (current == event.packageName) continue
                    close(event.timeMillis)
                    current = event.packageName
                    startedAt = event.timeMillis
                }
                UsageEvent.Type.Paused -> if (current == event.packageName) close(event.timeMillis)
                UsageEvent.Type.ScreenOff, UsageEvent.Type.Shutdown -> close(event.timeMillis)
            }
        }
        close(to)

        return totals
    }

    /** Milliseconds -> whole seconds, dropping apps used for less than a second. */
    fun toSeconds(millis: Map<String, Long>): Map<String, Int> =
        millis.mapValues { (it.value / 1000).toInt() }.filterValues { it > 0 }
}
