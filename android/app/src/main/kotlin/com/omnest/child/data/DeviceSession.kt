package com.omnest.child.data

import android.content.Context
import android.content.SharedPreferences
import androidx.core.content.edit
import androidx.security.crypto.EncryptedSharedPreferences
import androidx.security.crypto.MasterKey
import dagger.hilt.android.qualifiers.ApplicationContext
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import javax.inject.Inject
import javax.inject.Singleton

data class PairedDevice(
    val token: String,
    val deviceId: Long,
    val childId: Long,
    val childName: String,
    val ageTier: String,
)

/**
 * The per-device token and who this phone belongs to, kept in EncryptedSharedPreferences
 * (AES-256, key in the Android Keystore). Excluded from backups (res/xml/data_extraction_rules.xml).
 */
@Singleton
class DeviceSession @Inject constructor(@ApplicationContext context: Context) {

    private val prefs: SharedPreferences = EncryptedSharedPreferences.create(
        context,
        FILE,
        MasterKey.Builder(context).setKeyScheme(MasterKey.KeyScheme.AES256_GCM).build(),
        EncryptedSharedPreferences.PrefKeyEncryptionScheme.AES256_SIV,
        EncryptedSharedPreferences.PrefValueEncryptionScheme.AES256_GCM,
    )

    private val _current = MutableStateFlow(read())
    val current: StateFlow<PairedDevice?> = _current.asStateFlow()

    val authHeader: String? get() = _current.value?.let { "Bearer ${it.token}" }

    fun save(device: PairedDevice) {
        prefs.edit {
            putString(KEY_TOKEN, device.token)
            putLong(KEY_DEVICE_ID, device.deviceId)
            putLong(KEY_CHILD_ID, device.childId)
            putString(KEY_CHILD_NAME, device.childName)
            putString(KEY_AGE_TIER, device.ageTier)
        }
        _current.value = device
    }

    fun clear() {
        prefs.edit { clear() }
        _current.value = null
    }

    private fun read(): PairedDevice? {
        val token = prefs.getString(KEY_TOKEN, null) ?: return null
        return PairedDevice(
            token = token,
            deviceId = prefs.getLong(KEY_DEVICE_ID, 0),
            childId = prefs.getLong(KEY_CHILD_ID, 0),
            childName = prefs.getString(KEY_CHILD_NAME, "") ?: "",
            ageTier = prefs.getString(KEY_AGE_TIER, "kid") ?: "kid",
        )
    }

    private companion object {
        const val FILE = "omnest_device_session"
        const val KEY_TOKEN = "device_token"
        const val KEY_DEVICE_ID = "device_id"
        const val KEY_CHILD_ID = "child_id"
        const val KEY_CHILD_NAME = "child_name"
        const val KEY_AGE_TIER = "age_tier"
    }
}
