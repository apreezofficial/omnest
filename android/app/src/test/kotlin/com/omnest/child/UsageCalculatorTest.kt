package com.omnest.child

import com.omnest.child.usage.UsageCalculator
import com.omnest.child.usage.UsageEvent
import com.omnest.child.usage.UsageEvent.Type.Paused
import com.omnest.child.usage.UsageEvent.Type.Resumed
import com.omnest.child.usage.UsageEvent.Type.ScreenOff
import com.omnest.child.usage.UsageEvent.Type.Shutdown
import org.junit.Assert.assertEquals
import org.junit.Test

class UsageCalculatorTest {

    private val min = 60_000L
    private fun e(pkg: String, type: UsageEvent.Type, minute: Long) = UsageEvent(pkg, type, minute * min)

    @Test
    fun countsSimpleSessions() {
        val events = listOf(
            e("tiktok", Resumed, 0), e("tiktok", Paused, 30),
            e("whatsapp", Resumed, 30), e("whatsapp", Paused, 40),
        )
        assertEquals(mapOf("tiktok" to 30 * min, "whatsapp" to 10 * min), UsageCalculator.foregroundMillis(events, 0, 120 * min))
    }

    @Test
    fun switchingAppsWithoutPauseClosesThePreviousOne() {
        val events = listOf(e("a", Resumed, 0), e("b", Resumed, 5), e("b", Paused, 8))
        assertEquals(mapOf("a" to 5 * min, "b" to 3 * min), UsageCalculator.foregroundMillis(events, 0, 60 * min))
    }

    @Test
    fun activitiesOfTheSameAppDontDoubleCount() {
        val events = listOf(
            e("a", Resumed, 0),
            e("a", Resumed, 2), // second activity of a, before the first is paused
            e("a", Paused, 3),
            e("a", Resumed, 3), e("a", Paused, 10),
        )
        assertEquals(mapOf("a" to 10 * min), UsageCalculator.foregroundMillis(events, 0, 60 * min))
    }

    @Test
    fun screenOffAndShutdownStopTheClock() {
        val events = listOf(
            e("a", Resumed, 0), e("", ScreenOff, 10), e("a", Paused, 50),
            e("b", Resumed, 60), e("", Shutdown, 65),
        )
        assertEquals(mapOf("a" to 10 * min, "b" to 5 * min), UsageCalculator.foregroundMillis(events, 0, 120 * min))
    }

    @Test
    fun clipsSessionsAcrossMidnightAndRunsOpenSessionToNow() {
        // Day window is [100, 200). Session 90..110 counts 10; open session from 190 counts to 200.
        val events = listOf(e("a", Resumed, 90), e("a", Paused, 110), e("b", Resumed, 190))
        assertEquals(mapOf("a" to 10 * min, "b" to 10 * min), UsageCalculator.foregroundMillis(events, 100 * min, 200 * min))
    }

    @Test
    fun ignoresExcludedAppsAndUnorderedInput() {
        val events = listOf(e("launcher", Paused, 5), e("a", Resumed, 5), e("launcher", Resumed, 0), e("a", Paused, 9))
        assertEquals(mapOf("a" to 4 * min), UsageCalculator.foregroundMillis(events, 0, 60 * min, exclude = setOf("launcher")))
    }

    @Test
    fun toSecondsDropsSubSecondNoise() {
        assertEquals(mapOf("a" to 61), UsageCalculator.toSeconds(mapOf("a" to 61_900L, "b" to 400L)))
    }
}
