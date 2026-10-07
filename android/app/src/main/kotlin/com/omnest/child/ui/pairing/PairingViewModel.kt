package com.omnest.child.ui.pairing

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.omnest.child.data.PairOutcome
import com.omnest.child.data.PairingRepository
import com.omnest.child.data.PairedDevice
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import javax.inject.Inject

data class PairingUiState(
    val code: String = "",
    val loading: Boolean = false,
    val error: String? = null,
    val paired: PairedDevice? = null,
) {
    val canSubmit: Boolean get() = code.length == CODE_LENGTH && !loading
}

const val CODE_LENGTH = 6

/** Code from a pairing QR (omnest://pair?code=123456), or null for any other QR. */
fun parsePairingQr(raw: String): String? =
    Regex("""^omnest://pair\?(?:.*&)?code=(\d{6})(?:&|$)""").find(raw.trim())?.groupValues?.get(1)

@HiltViewModel
class PairingViewModel @Inject constructor(private val repository: PairingRepository) : ViewModel() {

    private val _state = MutableStateFlow(PairingUiState())
    val state: StateFlow<PairingUiState> = _state.asStateFlow()

    fun onCodeChange(code: String) {
        _state.update { it.copy(code = code, error = null) }
        if (code.length == CODE_LENGTH) submit()
    }

    /** From a scanned QR: omnest://pair?code=123456 */
    fun onScanned(raw: String) {
        val code = parsePairingQr(raw)
        if (code == null) {
            _state.update { it.copy(error = "That QR code isn't an Omnest pairing code.") }
            return
        }
        onCodeChange(code)
    }

    fun submit() {
        val current = _state.value
        if (!current.canSubmit) return

        _state.update { it.copy(loading = true, error = null) }
        viewModelScope.launch {
            val outcome = repository.pair(current.code)
            _state.update {
                when (outcome) {
                    is PairOutcome.Paired -> it.copy(loading = false, paired = outcome.device)
                    is PairOutcome.Rejected -> it.copy(loading = false, error = outcome.message, code = "")
                    is PairOutcome.Failed -> it.copy(loading = false, error = outcome.message)
                    PairOutcome.Offline -> it.copy(
                        loading = false,
                        error = "No internet connection. Connect to Wi-Fi or data, then try again.",
                    )
                }
            }
        }
    }
}
