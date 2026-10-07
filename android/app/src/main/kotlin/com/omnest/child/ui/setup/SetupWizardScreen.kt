package com.omnest.child.ui.setup

import android.Manifest
import android.os.Build
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
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
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableIntStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.saveable.rememberSaveable
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.semantics.contentDescription
import androidx.compose.ui.semantics.semantics
import androidx.compose.ui.unit.dp
import androidx.lifecycle.compose.LifecycleResumeEffect
import com.adamglin.PhosphorIcons
import com.adamglin.phosphoricons.Bold
import com.adamglin.phosphoricons.bold.AppWindow
import com.adamglin.phosphoricons.bold.BatteryCharging
import com.adamglin.phosphoricons.bold.BellRinging
import com.adamglin.phosphoricons.bold.ChartBar
import com.adamglin.phosphoricons.bold.LockKey
import com.adamglin.phosphoricons.bold.RocketLaunch
import com.omnest.child.permissions.OemGuide
import com.omnest.child.permissions.Requirement
import com.omnest.child.permissions.startFirstAvailable
import com.omnest.child.ui.components.ButtonVariant
import com.omnest.child.ui.components.OmnestButton
import com.omnest.child.ui.theme.OmnestDimens
import com.omnest.child.ui.theme.OmnestShapes
import com.omnest.child.ui.theme.OmnestSpacing
import com.omnest.child.ui.theme.OmnestTheme

/** A wizard step: one permission, or the brand-specific background guide at the end. */
private sealed interface Step {
    data class Permission(val requirement: Requirement) : Step
    data class Oem(val guide: OemGuide) : Step
}

/**
 * One permission per screen, plain-language reason, auto-advances when the user comes back
 * from Settings with it granted. Missing permissions are re-detected later on Home.
 */
@Composable
fun SetupWizardScreen(onFinished: () -> Unit, only: List<Requirement>? = null) {
    val context = LocalContext.current
    val steps = remember {
        val perms = (only ?: Requirement.missing(context)).map { Step.Permission(it) }
        val oem = if (only == null) OemGuide.forThisPhone()?.let { Step.Oem(it) } else null
        perms + listOfNotNull(oem)
    }
    var index by rememberSaveable { mutableIntStateOf(0) }
    var recheck by remember { mutableIntStateOf(0) }

    fun next() {
        if (index >= steps.lastIndex) onFinished() else index++
    }

    if (steps.isEmpty()) {
        LaunchedEffect(Unit) { onFinished() }
        return
    }

    val step = steps[index]
    val granted = remember(step, recheck) {
        (step as? Step.Permission)?.requirement?.isGranted(context) ?: false
    }

    // Back from Settings: re-check, and move on if it was granted.
    LifecycleResumeEffect(step) {
        recheck++
        if ((step as? Step.Permission)?.requirement?.isGranted(context) == true) next()
        onPauseOrDispose { }
    }

    val notificationLauncher = rememberLauncherForActivityResult(ActivityResultContracts.RequestPermission()) { ok ->
        if (ok) next() else context.startFirstAvailable(listOf(Requirement.Notifications.settingsIntent(context)))
    }

    StepLayout(
        position = index + 1,
        total = steps.size,
        icon = step.icon(),
        title = when (step) {
            is Step.Permission -> step.requirement.title
            is Step.Oem -> "One more thing for your ${step.guide.brandName}"
        },
        body = {
            when (step) {
                is Step.Permission -> Text(step.requirement.reason, style = OmnestTheme.type.body, color = OmnestTheme.colors.textMuted)
                is Step.Oem -> OemSteps(step.guide)
            }
        },
        primaryLabel = when {
            granted -> "Next"
            step is Step.Permission -> step.requirement.action
            else -> "Open settings"
        },
        onPrimary = {
            when {
                granted -> next()
                step is Step.Permission && step.requirement == Requirement.Notifications &&
                    Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU ->
                    notificationLauncher.launch(Manifest.permission.POST_NOTIFICATIONS)
                step is Step.Permission -> context.startFirstAvailable(
                    listOf(step.requirement.settingsIntent(context), step.requirement.fallbackIntent(context)),
                )
                step is Step.Oem -> context.startFirstAvailable(step.guide.intents(context))
            }
        },
        secondaryLabel = if (step is Step.Oem) "Done" else "Skip for now",
        onSecondary = ::next,
    )
}

private fun Step.icon(): ImageVector = when (this) {
    is Step.Oem -> PhosphorIcons.Bold.RocketLaunch
    is Step.Permission -> when (requirement) {
        Requirement.UsageAccess -> PhosphorIcons.Bold.ChartBar
        Requirement.Overlay -> PhosphorIcons.Bold.AppWindow
        Requirement.Notifications -> PhosphorIcons.Bold.BellRinging
        Requirement.Battery -> PhosphorIcons.Bold.BatteryCharging
        Requirement.DeviceAdmin -> PhosphorIcons.Bold.LockKey
    }
}

@Composable
private fun OemSteps(guide: OemGuide) {
    val c = OmnestTheme.colors
    Column(verticalArrangement = Arrangement.spacedBy(OmnestSpacing.s3)) {
        Text(
            "Your phone may close Omnest to save battery. These steps stop that:",
            style = OmnestTheme.type.body,
            color = c.textMuted,
        )
        guide.steps.forEachIndexed { i, text ->
            Row(horizontalArrangement = Arrangement.spacedBy(OmnestSpacing.s3)) {
                Box(
                    modifier = Modifier.size(28.dp).background(c.primaryTint, OmnestShapes.full),
                    contentAlignment = Alignment.Center,
                ) {
                    Text("${i + 1}", style = OmnestTheme.type.label, color = c.text)
                }
                Text(text, style = OmnestTheme.type.body, color = c.text, modifier = Modifier.weight(1f))
            }
        }
    }
}

@Composable
private fun StepLayout(
    position: Int,
    total: Int,
    icon: ImageVector,
    title: String,
    body: @Composable () -> Unit,
    primaryLabel: String,
    onPrimary: () -> Unit,
    secondaryLabel: String,
    onSecondary: () -> Unit,
) {
    val c = OmnestTheme.colors
    Column(
        modifier = Modifier
            .fillMaxSize()
            .background(c.bg)
            .safeDrawingPadding()
            .padding(horizontal = OmnestSpacing.screen, vertical = OmnestSpacing.s6),
    ) {
        // Segmented progress bar.
        Row(
            horizontalArrangement = Arrangement.spacedBy(OmnestSpacing.s1),
            modifier = Modifier.fillMaxWidth().semantics { contentDescription = "Step $position of $total" },
        ) {
            repeat(total) { i ->
                Box(
                    Modifier
                        .weight(1f)
                        .height(8.dp)
                        .background(if (i < position) c.primary else c.border, OmnestShapes.full),
                )
            }
        }
        Spacer(Modifier.height(OmnestSpacing.s2))
        Text("Step $position of $total", style = OmnestTheme.type.caption, color = c.textSubtle)

        Column(
            modifier = Modifier.weight(1f).verticalScroll(rememberScrollState()).padding(vertical = OmnestSpacing.s8),
        ) {
            Box(
                modifier = Modifier
                    .size(72.dp)
                    .background(c.primaryTint, OmnestShapes.lg)
                    .border(OmnestDimens.borderBold, c.borderStrong, OmnestShapes.lg),
                contentAlignment = Alignment.Center,
            ) {
                Icon(icon, contentDescription = null, tint = c.text, modifier = Modifier.size(32.dp))
            }
            Spacer(Modifier.height(OmnestSpacing.s6))
            Text(title, style = OmnestTheme.type.headline, color = c.text)
            Spacer(Modifier.height(OmnestSpacing.s3))
            body()
        }

        Column(verticalArrangement = Arrangement.spacedBy(OmnestSpacing.s2)) {
            OmnestButton(
                text = primaryLabel,
                onClick = onPrimary,
                height = OmnestDimens.buttonLg,
                modifier = Modifier.fillMaxWidth(),
            )
            OmnestButton(
                text = secondaryLabel,
                onClick = onSecondary,
                variant = ButtonVariant.Ghost,
                modifier = Modifier.fillMaxWidth(),
            )
        }
    }
}

@androidx.compose.ui.tooling.preview.Preview(widthDp = 360, heightDp = 720)
@Composable
private fun StepPreview() {
    OmnestTheme {
        StepLayout(
            position = 2,
            total = 6,
            icon = PhosphorIcons.Bold.AppWindow,
            title = Requirement.Overlay.title,
            body = { Text(Requirement.Overlay.reason, style = OmnestTheme.type.body, color = OmnestTheme.colors.textMuted) },
            primaryLabel = Requirement.Overlay.action,
            onPrimary = {},
            secondaryLabel = "Skip for now",
            onSecondary = {},
        )
    }
}
