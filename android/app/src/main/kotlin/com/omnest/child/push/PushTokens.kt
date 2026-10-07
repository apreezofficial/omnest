package com.omnest.child.push

import android.content.Context
import com.google.firebase.FirebaseApp
import com.google.firebase.messaging.FirebaseMessaging
import dagger.hilt.android.qualifiers.ApplicationContext
import kotlinx.coroutines.tasks.await
import kotlinx.coroutines.withTimeoutOrNull
import javax.inject.Inject
import javax.inject.Singleton

/**
 * FCM token access that degrades gracefully: builds without google-services.json have no
 * FirebaseApp, and some phones have no Play services. Pairing must still work then.
 */
@Singleton
class PushTokens @Inject constructor(@ApplicationContext private val context: Context) {

    val available: Boolean
        get() = FirebaseApp.getApps(context).isNotEmpty()

    suspend fun currentOrNull(): String? {
        if (!available) return null
        return runCatching {
            withTimeoutOrNull(5_000) { FirebaseMessaging.getInstance().token.await() }
        }.getOrNull()
    }
}
