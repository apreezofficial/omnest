package com.omnest.child.ui.components

import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.aspectRatio
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.widthIn
import androidx.compose.foundation.text.BasicTextField
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.focus.FocusRequester
import androidx.compose.ui.focus.focusRequester
import androidx.compose.ui.focus.onFocusChanged
import androidx.compose.ui.semantics.contentDescription
import androidx.compose.ui.semantics.error
import androidx.compose.ui.semantics.semantics
import androidx.compose.ui.text.input.ImeAction
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.unit.dp
import com.omnest.child.ui.theme.OmnestDimens
import com.omnest.child.ui.theme.OmnestShapes
import com.omnest.child.ui.theme.OmnestTheme

/**
 * 6-digit pairing code input (docs/specs.md §9 Inputs): one real text field drawn as six boxes,
 * so paste, autofill and TalkBack all behave like a normal field.
 * Boxes keep the 56x64 ratio and shrink to fit 360dp-wide phones.
 */
@Composable
fun CodeInput(
    value: String,
    onValueChange: (String) -> Unit,
    modifier: Modifier = Modifier,
    length: Int = 6,
    isError: Boolean = false,
    enabled: Boolean = true,
    onDone: () -> Unit = {},
) {
    val c = OmnestTheme.colors
    val focus = remember { FocusRequester() }
    var focused by remember { mutableStateOf(false) }

    LaunchedEffect(Unit) { focus.requestFocus() }

    BasicTextField(
        value = value,
        onValueChange = { input -> onValueChange(input.filter(Char::isDigit).take(length)) },
        enabled = enabled,
        singleLine = true,
        keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.NumberPassword, imeAction = ImeAction.Done),
        keyboardActions = androidx.compose.foundation.text.KeyboardActions(onDone = { onDone() }),
        modifier = modifier
            .focusRequester(focus)
            .onFocusChanged { focused = it.isFocused }
            .semantics {
                contentDescription = "Pairing code, $length digits"
                if (isError) error("Code not accepted")
            },
        decorationBox = {
            Row(
                modifier = Modifier.fillMaxWidth().widthIn(max = 376.dp),
                horizontalArrangement = Arrangement.spacedBy(8.dp),
            ) {
                repeat(length) { index ->
                    val char = value.getOrNull(index)
                    val active = focused && enabled && index == value.length.coerceAtMost(length - 1)
                    val borderColor = when {
                        isError -> c.danger
                        active -> c.primary
                        else -> c.borderStrong
                    }
                    Box(
                        modifier = Modifier
                            .weight(1f)
                            .aspectRatio(56f / 64f)
                            .background(c.surface, OmnestShapes.md)
                            .border(if (active) 3.dp else OmnestDimens.borderBold, borderColor, OmnestShapes.md),
                        contentAlignment = Alignment.Center,
                    ) {
                        Text(
                            text = char?.toString() ?: "",
                            style = OmnestTheme.type.code,
                            color = c.text,
                        )
                    }
                }
            }
        },
    )
}
