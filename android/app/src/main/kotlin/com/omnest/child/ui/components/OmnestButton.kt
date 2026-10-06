package com.omnest.child.ui.components

import androidx.compose.animation.core.animateDpAsState
import androidx.compose.animation.core.tween
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.interaction.MutableInteractionSource
import androidx.compose.foundation.interaction.collectIsPressedAsState
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.offset
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.material3.Icon
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.remember
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.Shape
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.semantics.Role
import androidx.compose.ui.unit.Dp
import androidx.compose.ui.unit.dp
import com.omnest.child.ui.theme.OmnestDimens
import com.omnest.child.ui.theme.OmnestShapes
import com.omnest.child.ui.theme.OmnestSpacing
import com.omnest.child.ui.theme.OmnestTheme

enum class ButtonVariant { Primary, Accent, Secondary, Ghost, Danger }

/**
 * Bold, flat button with the signature hard shadow (docs/specs.md §6, §9).
 * The shadow is a solid offset shape behind the button; pressing slides the button onto it.
 */
@Composable
fun OmnestButton(
    text: String,
    onClick: () -> Unit,
    modifier: Modifier = Modifier,
    variant: ButtonVariant = ButtonVariant.Primary,
    height: Dp = OmnestDimens.buttonMd,
    shape: Shape = OmnestShapes.md,
    enabled: Boolean = true,
    icon: ImageVector? = null,
) {
    val c = OmnestTheme.colors
    val interaction = remember { MutableInteractionSource() }
    val pressed by interaction.collectIsPressedAsState()
    val bold = variant != ButtonVariant.Ghost

    val (fill, content) = when {
        !enabled && bold -> c.disabled to c.textMuted
        variant == ButtonVariant.Primary -> (if (pressed) c.primaryPressed else c.primary) to c.onPrimary
        variant == ButtonVariant.Accent -> (if (pressed) c.accentPressed else c.accent) to c.onAccent
        variant == ButtonVariant.Secondary -> (if (pressed) c.surfaceSubtle else c.surface) to c.text
        variant == ButtonVariant.Danger -> c.danger to c.surface
        else -> Color.Transparent to (if (enabled) c.primary else c.disabled)
    }
    val showShadow = bold && enabled
    val drop by animateDpAsState(
        targetValue = if (pressed && showShadow) OmnestDimens.shadowHard else 0.dp,
        animationSpec = tween(durationMillis = 120),
        label = "press",
    )

    Box(modifier = modifier.height(height + OmnestDimens.shadowHard)) {
        if (showShadow) {
            Box(
                Modifier
                    .offset(y = OmnestDimens.shadowHard)
                    .fillMaxWidth()
                    .height(height)
                    .background(c.borderStrong, shape),
            )
        }
        Row(
            modifier = Modifier
                .offset(y = drop)
                .fillMaxWidth()
                .height(height)
                .background(fill, shape)
                .then(
                    if (bold) Modifier.border(OmnestDimens.borderBold, if (enabled) c.borderStrong else c.disabled, shape)
                    else Modifier,
                )
                .clickable(
                    interactionSource = interaction,
                    indication = null,
                    enabled = enabled,
                    role = Role.Button,
                    onClick = onClick,
                )
                .padding(horizontal = OmnestSpacing.s5),
            horizontalArrangement = Arrangement.Center,
            verticalAlignment = Alignment.CenterVertically,
        ) {
            if (icon != null) {
                Icon(icon, contentDescription = null, tint = content, modifier = Modifier.size(24.dp))
                Spacer(Modifier.width(OmnestSpacing.s2))
            }
            Text(text, style = OmnestTheme.type.label, color = content)
        }
    }
}
