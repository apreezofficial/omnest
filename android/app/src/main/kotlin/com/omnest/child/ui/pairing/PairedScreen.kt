package com.omnest.child.ui.pairing

import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.safeDrawingPadding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.widthIn
import androidx.compose.material3.Icon
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.tooling.preview.Preview
import androidx.compose.ui.unit.dp
import com.adamglin.PhosphorIcons
import com.adamglin.phosphoricons.Fill
import com.adamglin.phosphoricons.fill.CheckCircle
import com.omnest.child.ui.components.OmnestButton
import com.omnest.child.ui.theme.OmnestDimens
import com.omnest.child.ui.theme.OmnestShapes
import com.omnest.child.ui.theme.OmnestSpacing
import com.omnest.child.ui.theme.OmnestTheme

@Composable
fun PairedScreen(childName: String, onContinue: () -> Unit) {
    val c = OmnestTheme.colors

    Column(
        modifier = Modifier
            .fillMaxSize()
            .background(c.bg)
            .safeDrawingPadding()
            .padding(horizontal = OmnestSpacing.screen, vertical = OmnestSpacing.s8),
        verticalArrangement = Arrangement.Center,
        horizontalAlignment = Alignment.CenterHorizontally,
    ) {
        Box(
            modifier = Modifier
                .fillMaxWidth()
                .background(c.successBg, OmnestShapes.xl)
                .border(OmnestDimens.borderBold, c.borderStrong, OmnestShapes.xl)
                .padding(OmnestSpacing.s6),
            contentAlignment = Alignment.Center,
        ) {
            Column(horizontalAlignment = Alignment.CenterHorizontally) {
                Icon(PhosphorIcons.Fill.CheckCircle, contentDescription = null, tint = c.success, modifier = Modifier.size(56.dp))
                Spacer(Modifier.height(OmnestSpacing.s4))
                Text(
                    "All set, $childName!",
                    style = OmnestTheme.type.headline,
                    color = c.text,
                    textAlign = TextAlign.Center,
                )
                Spacer(Modifier.height(OmnestSpacing.s2))
                Text(
                    "This phone is now linked to your parent's Omnest. Next, a few quick settings so your limits work.",
                    style = OmnestTheme.type.body,
                    color = c.textMuted,
                    textAlign = TextAlign.Center,
                )
            }
        }
        Spacer(Modifier.height(OmnestSpacing.s8))
        OmnestButton(
            text = "Continue",
            onClick = onContinue,
            height = OmnestDimens.buttonLg,
            modifier = Modifier.fillMaxWidth().widthIn(max = OmnestDimens.knockButtonMaxWidth),
        )
    }
}

@Preview(widthDp = 360, heightDp = 720)
@Composable
private fun PairedPreview() {
    OmnestTheme { PairedScreen(childName = "Tobi", onContinue = {}) }
}
