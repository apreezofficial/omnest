package com.omnest.child.ui.theme

import androidx.compose.foundation.isSystemInDarkTheme
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.darkColorScheme
import androidx.compose.material3.lightColorScheme
import androidx.compose.runtime.Composable
import androidx.compose.runtime.CompositionLocalProvider
import androidx.compose.runtime.ReadOnlyComposable

/**
 * Omnest theme. Read tokens through [OmnestTheme.colors] / [OmnestTheme.type].
 * Material 3 is mapped too so stock components (text fields, dialogs) pick up the palette.
 */
@Composable
fun OmnestTheme(darkTheme: Boolean = isSystemInDarkTheme(), content: @Composable () -> Unit) {
    val colors = if (darkTheme) DarkSemanticColors else LightSemanticColors
    val material = if (darkTheme) {
        darkColorScheme(
            primary = colors.primary, onPrimary = colors.onPrimary,
            secondary = colors.accent, onSecondary = colors.onAccent,
            background = colors.bg, onBackground = colors.text,
            surface = colors.surface, onSurface = colors.text, onSurfaceVariant = colors.textMuted,
            outline = colors.borderStrong, outlineVariant = colors.border,
            error = colors.danger,
        )
    } else {
        lightColorScheme(
            primary = colors.primary, onPrimary = colors.onPrimary,
            secondary = colors.accent, onSecondary = colors.onAccent,
            background = colors.bg, onBackground = colors.text,
            surface = colors.surface, onSurface = colors.text, onSurfaceVariant = colors.textMuted,
            outline = colors.borderStrong, outlineVariant = colors.border,
            error = colors.danger,
        )
    }

    CompositionLocalProvider(
        LocalOmnestColors provides colors,
        LocalOmnestTypography provides OmnestTypography(),
    ) {
        MaterialTheme(colorScheme = material, content = content)
    }
}

object OmnestTheme {
    val colors: OmnestSemanticColors
        @Composable @ReadOnlyComposable get() = LocalOmnestColors.current

    val type: OmnestTypography
        @Composable @ReadOnlyComposable get() = LocalOmnestTypography.current
}
