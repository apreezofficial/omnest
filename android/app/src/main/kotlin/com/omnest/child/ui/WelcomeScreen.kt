package com.omnest.child.ui

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.safeDrawingPadding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.widthIn
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.res.stringResource
import androidx.compose.ui.tooling.preview.Preview
import androidx.compose.ui.unit.dp
import com.omnest.child.R
import com.omnest.child.ui.components.ButtonVariant
import com.omnest.child.ui.components.LogoMark
import com.omnest.child.ui.components.OmnestButton
import com.omnest.child.ui.theme.OmnestDimens
import com.omnest.child.ui.theme.OmnestSpacing
import com.omnest.child.ui.theme.OmnestTheme

@Composable
fun WelcomeScreen(onHaveCode: () -> Unit) {
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
        LogoMark(modifier = Modifier.size(96.dp))
        Spacer(Modifier.height(OmnestSpacing.s8))
        Text(stringResource(R.string.welcome_title), style = OmnestTheme.type.headline, color = c.text)
        Spacer(Modifier.height(OmnestSpacing.s3))
        Text(stringResource(R.string.welcome_body), style = OmnestTheme.type.body, color = c.textMuted)
        Spacer(Modifier.height(OmnestSpacing.s10))
        OmnestButton(
            text = stringResource(R.string.welcome_cta),
            onClick = onHaveCode,
            variant = ButtonVariant.Primary,
            height = OmnestDimens.buttonLg,
            modifier = Modifier.fillMaxWidth().widthIn(max = OmnestDimens.knockButtonMaxWidth),
        )
    }
}

@Preview(widthDp = 360, heightDp = 720)
@Composable
private fun WelcomePreview() {
    OmnestTheme { WelcomeScreen(onHaveCode = {}) }
}

@Preview(widthDp = 360, heightDp = 720)
@Composable
private fun WelcomeDarkPreview() {
    OmnestTheme(darkTheme = true) { WelcomeScreen(onHaveCode = {}) }
}
