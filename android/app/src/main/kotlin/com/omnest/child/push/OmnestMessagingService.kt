package com.omnest.child.push

import android.util.Log
import com.google.firebase.messaging.FirebaseMessagingService
import com.google.firebase.messaging.RemoteMessage

/**
 * FCM is used for data messages only: "rules updated", lock/unlock, Knock decisions.
 * Handlers are added in Phases 3-5; token upload to the API lands in Phase 1.
 */
class OmnestMessagingService : FirebaseMessagingService() {

    override fun onNewToken(token: String) {
        Log.d(TAG, "FCM token refreshed")
        // Phase 1: enqueue a WorkManager job that sends the token to POST /device/fcm-token.
    }

    override fun onMessageReceived(message: RemoteMessage) {
        val type = message.data["type"] ?: return
        Log.d(TAG, "Push received: $type")
    }

    private companion object {
        const val TAG = "OmnestPush"
    }
}
