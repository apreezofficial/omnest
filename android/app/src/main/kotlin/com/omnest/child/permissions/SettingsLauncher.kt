package com.omnest.child.permissions

import android.content.ActivityNotFoundException
import android.content.Context
import android.content.Intent

/** Tries each intent in order; OEM screens move between ROM versions, so failures are expected. */
fun Context.startFirstAvailable(intents: List<Intent>): Boolean {
    for (intent in intents) {
        try {
            startActivity(intent.addFlags(Intent.FLAG_ACTIVITY_NEW_TASK))
            return true
        } catch (_: ActivityNotFoundException) {
        } catch (_: SecurityException) {
        }
    }
    return false
}
