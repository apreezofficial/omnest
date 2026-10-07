package com.omnest.child.admin

import android.app.admin.DeviceAdminReceiver
import android.content.Context
import android.content.Intent

/**
 * Device admin: remote lock (DevicePolicyManager.lockNow, Phase 5) and uninstall protection.
 * Policies are limited to force-lock (res/xml/device_admin.xml).
 */
class OmnestDeviceAdmin : DeviceAdminReceiver() {

    override fun onDisableRequested(context: Context, intent: Intent): CharSequence =
        "Turning this off lets Omnest be removed and stops your parent from locking this phone. They'll be told."

    override fun onDisabled(context: Context, intent: Intent) {
        // Phase 5: report "device admin removed" to the parent.
    }
}
