package com.omnest.child.ui.home

import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.safeDrawingPadding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.Icon
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.lifecycle.compose.LifecycleResumeEffect
import com.adamglin.PhosphorIcons
import com.adamglin.phosphoricons.Bold
import com.adamglin.phosphoricons.bold.WarningCircle
import com.omnest.child.permissions.Requirement
import com.omnest.child.ui.components.ButtonVariant
import com.omnest.child.ui.components.LogoMark
import com.omnest.child.ui.components.OmnestButton
import com.omnest.child.ui.theme.OmnestDimens
import com.omnest.child.ui.theme.OmnestShapes
import com.omnest.child.ui.theme.OmnestSpacing
import com.omnest.child.ui.theme.OmnestTheme

/** Placeholder home until Phase 6 (time ring, rules in plain language), plus missing-permission check. */
@Composable
fun HomeScreen(childName: String, onFixPermissions: (List<Requirement>) -> Unit) {
    val context = LocalContext.current
    var missing by remember { mutableStateOf(Requirement.missing(context)) }

    // Re-check every time the app comes to the front: permissions can be revoked in Settings.
    LifecycleResumeEffect(Unit) {
        missing = Requirement.missing(context)
        onPauseOrDispose { }
    }

    HomeContent(childName = childName, missing = missing, onFix = { onFixPermissions(missing) })
}

@Composable
private fun HomeContent(childName: String, missing: List<Requirement>, onFix: () -> Unit) {
    val c = OmnestTheme.colors
    Column(
        modifier = Modifier
            .fillMaxSize()
            .background(c.bg)
            .safeDrawingPadding()
            .verticalScroll(rememberScrollState())
            .padding(horizontal = OmnestSpacing.screen, vertical = OmnestSpacing.s8),
        horizontalAlignment = Alignment.CenterHorizontally,
    ) {
        if (missing.isNotEmpty()) {
            MissingPermissionsCard(missing, onFix)
            Spacer(Modifier.height(OmnestSpacing.s8))
        }
        Spacer(Modifier.height(OmnestSpacing.s12))
        LogoMark(modifier = Modifier.size(72.dp))
        Spacer(Modifier.height(OmnestSpacing.s6))
        Text("Hi, $childName", style = OmnestTheme.type.headline, color = c.text, textAlign = TextAlign.Center)
        Spacer(Modifier.height(OmnestSpacing.s2))
        Text(
            "Omnest is set up on this phone. Your time for today will show here.",
            style = OmnestTheme.type.body,
            color = c.textMuted,
            textAlign = TextAlign.Center,
        )
    }
}

@Composable
private fun MissingPermissionsCard(missing: List<Requirement>, onFix: () -> Unit) {
    val c = OmnestTheme.colors
    Column(
        modifier = Modifier
            .fillMaxWidth()
            .background(c.accentTint, OmnestShapes.lg)
            .border(OmnestDimens.borderBold, c.borderStrong, OmnestShapes.lg)
            .padding(OmnestSpacing.s5),
        verticalArrangement = Arrangement.spacedBy(OmnestSpacing.s3),
    ) {
        Row(verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.spacedBy(OmnestSpacing.s2)) {
            Icon(PhosphorIcons.Bold.WarningCircle, contentDescription = null, tint = c.text, modifier = Modifier.size(24.dp))
            Text("Finish setting up", style = OmnestTheme.type.title, color = c.text)
        }
        Text(
            "Some settings are off, so limits may not work: " + missing.joinToString { it.title.lowercase() } + ".",
            style = OmnestTheme.type.bodySm,
            color = c.text,
        )
        OmnestButton(text = "Fix now", onClick = onFix, variant = ButtonVariant.Primary, modifier = Modifier.fillMaxWidth())
    }
}

@androidx.compose.ui.tooling.preview.Preview(widthDp = 360, heightDp = 720)
@Composable
private fun HomeMissingPreview() {
    OmnestTheme {
        HomeContent(childName = "Tobi", missing = listOf(Requirement.UsageAccess, Requirement.Battery), onFix = {})
    }
}
