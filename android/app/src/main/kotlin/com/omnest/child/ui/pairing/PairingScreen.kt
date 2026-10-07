package com.omnest.child.ui.pairing

import android.app.Activity
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.imePadding
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.safeDrawingPadding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.semantics.LiveRegionMode
import androidx.compose.ui.semantics.liveRegion
import androidx.compose.ui.semantics.semantics
import androidx.compose.ui.unit.dp
import androidx.hilt.lifecycle.viewmodel.compose.hiltViewModel
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import com.adamglin.PhosphorIcons
import com.adamglin.phosphoricons.Bold
import com.adamglin.phosphoricons.bold.ArrowLeft
import com.adamglin.phosphoricons.bold.QrCode
import com.google.mlkit.vision.barcode.common.Barcode
import com.google.mlkit.vision.codescanner.GmsBarcodeScannerOptions
import com.google.mlkit.vision.codescanner.GmsBarcodeScanning
import com.omnest.child.data.PairedDevice
import com.omnest.child.ui.components.ButtonVariant
import com.omnest.child.ui.components.CodeInput
import com.omnest.child.ui.components.OmnestButton
import com.omnest.child.ui.theme.OmnestDimens
import com.omnest.child.ui.theme.OmnestSpacing
import com.omnest.child.ui.theme.OmnestTheme

@Composable
fun PairingScreen(
    onBack: () -> Unit,
    onPaired: (PairedDevice) -> Unit,
    viewModel: PairingViewModel = hiltViewModel(),
) {
    val state by viewModel.state.collectAsStateWithLifecycle()
    val context = LocalContext.current

    LaunchedEffect(state.paired) {
        state.paired?.let(onPaired)
    }

    PairingContent(
        state = state,
        onBack = onBack,
        onCodeChange = viewModel::onCodeChange,
        onSubmit = viewModel::submit,
        onScan = {
            val activity = context as? Activity ?: return@PairingContent
            val options = GmsBarcodeScannerOptions.Builder().setBarcodeFormats(Barcode.FORMAT_QR_CODE).build()
            GmsBarcodeScanning.getClient(activity, options).startScan()
                .addOnSuccessListener { barcode -> barcode.rawValue?.let(viewModel::onScanned) }
        },
    )
}

@Composable
private fun PairingContent(
    state: PairingUiState,
    onBack: () -> Unit,
    onCodeChange: (String) -> Unit,
    onSubmit: () -> Unit,
    onScan: () -> Unit,
) {
    val c = OmnestTheme.colors

    Column(
        modifier = Modifier
            .fillMaxSize()
            .background(c.bg)
            .safeDrawingPadding()
            .imePadding()
            .verticalScroll(rememberScrollState())
            .padding(horizontal = OmnestSpacing.screen, vertical = OmnestSpacing.s4),
    ) {
        IconButton(onClick = onBack, modifier = Modifier.size(OmnestDimens.touchTarget)) {
            Icon(PhosphorIcons.Bold.ArrowLeft, contentDescription = "Back", tint = c.text)
        }
        Spacer(Modifier.height(OmnestSpacing.s6))
        Text("Enter the code", style = OmnestTheme.type.headline, color = c.text)
        Spacer(Modifier.height(OmnestSpacing.s2))
        Text(
            "Ask your parent to open Omnest and tap Get pairing code. Then type the 6 numbers here.",
            style = OmnestTheme.type.body,
            color = c.textMuted,
        )
        Spacer(Modifier.height(OmnestSpacing.s8))

        CodeInput(
            value = state.code,
            onValueChange = onCodeChange,
            isError = state.error != null,
            enabled = !state.loading,
            onDone = onSubmit,
            modifier = Modifier.fillMaxWidth(),
        )

        Spacer(Modifier.height(OmnestSpacing.s3))
        Text(
            text = state.error ?: "",
            style = OmnestTheme.type.bodySm,
            color = c.danger,
            modifier = Modifier.semantics { liveRegion = LiveRegionMode.Polite },
        )
        Spacer(Modifier.height(OmnestSpacing.s6))

        Column(verticalArrangement = Arrangement.spacedBy(OmnestSpacing.s3)) {
            OmnestButton(
                text = if (state.loading) "Pairing..." else "Pair this phone",
                onClick = onSubmit,
                enabled = state.canSubmit,
                height = OmnestDimens.buttonLg,
                modifier = Modifier.fillMaxWidth(),
            )
            OmnestButton(
                text = "Scan QR code instead",
                onClick = onScan,
                variant = ButtonVariant.Secondary,
                enabled = !state.loading,
                icon = PhosphorIcons.Bold.QrCode,
                modifier = Modifier.fillMaxWidth(),
            )
        }
        Spacer(Modifier.height(24.dp))
    }
}

@androidx.compose.ui.tooling.preview.Preview(widthDp = 360, heightDp = 720)
@Composable
private fun PairingPreview() {
    OmnestTheme {
        PairingContent(
            state = PairingUiState(code = "4829", error = null),
            onBack = {},
            onCodeChange = {},
            onSubmit = {},
            onScan = {},
        )
    }
}

@androidx.compose.ui.tooling.preview.Preview(widthDp = 360, heightDp = 720)
@Composable
private fun PairingErrorPreview() {
    OmnestTheme(darkTheme = true) {
        PairingContent(
            state = PairingUiState(code = "", error = "That code didn't work. Check it, or ask your parent for a new one."),
            onBack = {},
            onCodeChange = {},
            onSubmit = {},
            onScan = {},
        )
    }
}
