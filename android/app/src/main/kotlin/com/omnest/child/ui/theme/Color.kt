package com.omnest.child.ui.theme

import androidx.compose.runtime.Immutable
import androidx.compose.runtime.staticCompositionLocalOf
import androidx.compose.ui.graphics.Color

/** Core palette. Source of truth: docs/specs.md §2. */
object OmnestColors {
    val Cream50 = Color(0xFFFFFDF7)
    val Cream100 = Color(0xFFFBF6EA)
    val Cream200 = Color(0xFFF3EBD6)
    val Cream300 = Color(0xFFE6DCC0)
    val Green900 = Color(0xFF0F3524)
    val Green700 = Color(0xFF1F5B3A)
    val Green600 = Color(0xFF2A7449)
    val Green200 = Color(0xFFCFE5D3)
    val Green100 = Color(0xFFE5F1E6)
    val Sun600 = Color(0xFFDB8E10)
    val Sun500 = Color(0xFFF5A524)
    val Sun200 = Color(0xFFFCE3B0)
    val Ink900 = Color(0xFF16211B)
    val Ink700 = Color(0xFF3B4A41)
    val Ink500 = Color(0xFF6B7A70)
    val Ink300 = Color(0xFFB7C0B9)
    val Coral600 = Color(0xFFC93C2E)
    val Coral100 = Color(0xFFF9DAD5)
    val Sky600 = Color(0xFF2B6CB0)
    val Sky100 = Color(0xFFDCEAF7)
}

/** Semantic roles. Screens use these, never raw palette values, so dark mode just works. */
@Immutable
data class OmnestSemanticColors(
    val bg: Color,
    val surface: Color,
    val surfaceSubtle: Color,
    val border: Color,
    val borderStrong: Color,
    val text: Color,
    val textMuted: Color,
    val textSubtle: Color,
    val primary: Color,
    val primaryPressed: Color,
    val onPrimary: Color,
    val primaryTint: Color,
    val accent: Color,
    val accentPressed: Color,
    val onAccent: Color,
    val accentTint: Color,
    val success: Color,
    val successBg: Color,
    val danger: Color,
    val dangerBg: Color,
    val info: Color,
    val infoBg: Color,
    val disabled: Color,
    val isDark: Boolean,
)

val LightSemanticColors = OmnestSemanticColors(
    bg = OmnestColors.Cream100,
    surface = OmnestColors.Cream50,
    surfaceSubtle = OmnestColors.Cream200,
    border = OmnestColors.Cream300,
    borderStrong = OmnestColors.Ink900,
    text = OmnestColors.Ink900,
    textMuted = OmnestColors.Ink700,
    textSubtle = OmnestColors.Ink500,
    primary = OmnestColors.Green700,
    primaryPressed = OmnestColors.Green600,
    onPrimary = OmnestColors.Cream50,
    primaryTint = OmnestColors.Green200,
    accent = OmnestColors.Sun500,
    accentPressed = OmnestColors.Sun600,
    onAccent = OmnestColors.Ink900,
    accentTint = OmnestColors.Sun200,
    success = OmnestColors.Green700,
    successBg = OmnestColors.Green100,
    danger = OmnestColors.Coral600,
    dangerBg = OmnestColors.Coral100,
    info = OmnestColors.Sky600,
    infoBg = OmnestColors.Sky100,
    disabled = OmnestColors.Ink300,
    isDark = false,
)

val DarkSemanticColors = OmnestSemanticColors(
    bg = Color(0xFF0F1712),
    surface = Color(0xFF17221B),
    surfaceSubtle = Color(0xFF1E2B23),
    border = Color(0xFF2B3A31),
    borderStrong = Color(0xFFE8E2D0),
    text = Color(0xFFF1ECDD),
    textMuted = Color(0xFFB9C2B9),
    textSubtle = Color(0xFFB9C2B9),
    primary = Color(0xFF5FBF85),
    primaryPressed = Color(0xFF7ACD9A),
    onPrimary = Color(0xFF0F1712),
    primaryTint = OmnestColors.Green900,
    accent = Color(0xFFF5B544),
    accentPressed = Color(0xFFF7C369),
    onAccent = OmnestColors.Ink900,
    accentTint = Color(0xFF3A2E14),
    success = Color(0xFF5FBF85),
    successBg = OmnestColors.Green900,
    danger = Color(0xFFFF7A6B),
    dangerBg = Color(0xFF3A1A16),
    info = Color(0xFF7FB2E5),
    infoBg = Color(0xFF16263A),
    disabled = OmnestColors.Ink700,
    isDark = true,
)

val LocalOmnestColors = staticCompositionLocalOf { LightSemanticColors }
