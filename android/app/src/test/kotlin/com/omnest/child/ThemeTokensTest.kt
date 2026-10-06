package com.omnest.child

import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.luminance
import com.omnest.child.ui.theme.DarkSemanticColors
import com.omnest.child.ui.theme.LightSemanticColors
import org.junit.Assert.assertTrue
import org.junit.Test

/** Guards the contrast rules in docs/specs.md §2 and §12. */
class ThemeTokensTest {

    private fun contrast(a: Color, b: Color): Double {
        val (hi, lo) = listOf(a.luminance(), b.luminance()).sortedDescending()
        return (hi + 0.05) / (lo + 0.05)
    }

    @Test
    fun bodyTextMeetsAA() {
        for (c in listOf(LightSemanticColors, DarkSemanticColors)) {
            assertTrue("text on bg", contrast(c.text, c.bg) >= 4.5)
            assertTrue("muted on surface", contrast(c.textMuted, c.surface) >= 4.5)
            assertTrue("on-primary", contrast(c.onPrimary, c.primary) >= 4.5)
            assertTrue("on-accent", contrast(c.onAccent, c.accent) >= 4.5)
        }
    }

    @Test
    fun boldBordersMeetUiContrast() {
        for (c in listOf(LightSemanticColors, DarkSemanticColors)) {
            assertTrue(contrast(c.borderStrong, c.bg) >= 3.0)
        }
    }
}
