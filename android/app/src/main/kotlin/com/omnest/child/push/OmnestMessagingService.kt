package com.omnest.child.push

import android.util.Log
import com.google.firebase.messaging.FirebaseMessagingService
import com.google.firebase.messaging.RemoteMessage

/**
 * FCM is used for data messages only: "rules updated", lock/unlock, Knock decisions.
 * Message handlers are added in Phases 3-5.
 */
class OmnestMessagingService : FirebaseMessagingService() {

    override fun onNewToken(token: String) {
        FcmTokenWorker.enqueue(applicationContext, token)
    }

    override fun onMessageReceived(message: RemoteMessage) {
        val type = message.data["type"] ?: return
        Log.d(TAG, "Push received: $type")
    }

    private companion object {
        const val TAG = "OmnestPush"
    }
}
