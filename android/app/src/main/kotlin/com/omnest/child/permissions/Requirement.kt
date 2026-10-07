package com.omnest.child.permissions

import android.Manifest
import android.app.AppOpsManager
import android.app.admin.DevicePolicyManager
import android.content.ComponentName
import android.content.Context
import android.content.Intent
import android.content.pm.PackageManager
import android.net.Uri
import android.os.Build
import android.os.PowerManager
import android.os.Process
import android.provider.Settings
import androidx.core.content.ContextCompat
import com.omnest.child.admin.OmnestDeviceAdmin

/**
 * Everything Omnest needs from the phone, in the order the setup wizard asks for it.
 * Copy is plain-language and talks to whoever is holding the phone (often the child).
 */
enum class Requirement(
    val title: String,
    val reason: String,
    val action: String,
) {
    UsageAccess(
        title = "See which apps are used",
        reason = "This lets Omnest count screen time and know when a limit is reached. It doesn't read messages or photos.",
        action = "Open Usage access",
    ),
    Overlay(
        title = "Show the time's-up screen",
        reason = "When time runs out, Omnest shows a screen on top of the app, with a Knock button to ask for more.",
        action = "Allow display over apps",
    ),
    Notifications(
        title = "Get answers to your Knocks",
        reason = "So you see right away when your parent says yes or no.",
        action = "Allow notifications",
    ),
    Battery(
        title = "Keep Omnest running",
        reason = "Phones close apps to save battery. This keeps your limits working, even after a restart. It uses very little power.",
        action = "Allow background use",
    ),
    DeviceAdmin(
        title = "Lock the screen when asked",
        reason = "Lets your parent lock this phone from their dashboard, and stops Omnest from being removed by accident.",
        action = "Turn on",
    );

    fun isGranted(context: Context): Boolean = when (this) {
        UsageAccess -> hasUsageAccess(context)
        Overlay -> Settings.canDrawOverlays(context)
        Notifications -> Build.VERSION.SDK_INT < Build.VERSION_CODES.TIRAMISU ||
            ContextCompat.checkSelfPermission(context, Manifest.permission.POST_NOTIFICATIONS) == PackageManager.PERMISSION_GRANTED
        Battery -> context.getSystemService(PowerManager::class.java).isIgnoringBatteryOptimizations(context.packageName)
        DeviceAdmin -> context.getSystemService(DevicePolicyManager::class.java)
            .isAdminActive(ComponentName(context, OmnestDeviceAdmin::class.java))
    }

    /** Intent to the right settings screen. Notifications is a runtime prompt instead (see wizard). */
    fun settingsIntent(context: Context): Intent {
        val pkg = Uri.parse("package:${context.packageName}")
        return when (this) {
            UsageAccess -> Intent(Settings.ACTION_USAGE_ACCESS_SETTINGS).apply {
                // Some ROMs accept a package to jump straight to Omnest's row.
                data = pkg
            }
            Overlay -> Intent(Settings.ACTION_MANAGE_OVERLAY_PERMISSION, pkg)
            Notifications -> Intent(Settings.ACTION_APP_NOTIFICATION_SETTINGS)
                .putExtra(Settings.EXTRA_APP_PACKAGE, context.packageName)
            Battery -> Intent(Settings.ACTION_REQUEST_IGNORE_BATTERY_OPTIMIZATIONS, pkg)
            DeviceAdmin -> Intent(DevicePolicyManager.ACTION_ADD_DEVICE_ADMIN)
                .putExtra(DevicePolicyManager.EXTRA_DEVICE_ADMIN, ComponentName(context, OmnestDeviceAdmin::class.java))
                .putExtra(DevicePolicyManager.EXTRA_ADD_EXPLANATION, reason)
        }
    }

    /** Settings screens without the package shortcut, for ROMs that reject it. */
    fun fallbackIntent(context: Context): Intent = when (this) {
        UsageAccess -> Intent(Settings.ACTION_USAGE_ACCESS_SETTINGS)
        Battery -> Intent(Settings.ACTION_IGNORE_BATTERY_OPTIMIZATION_SETTINGS)
        else -> Intent(Settings.ACTION_APPLICATION_DETAILS_SETTINGS, Uri.parse("package:${context.packageName}"))
    }

    companion object {
        fun missing(context: Context): List<Requirement> = entries.filterNot { it.isGranted(context) }

        @Suppress("DEPRECATION")
        private fun hasUsageAccess(context: Context): Boolean {
            val ops = context.getSystemService(AppOpsManager::class.java)
            val mode = if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.Q) {
                ops.unsafeCheckOpNoThrow(AppOpsManager.OPSTR_GET_USAGE_STATS, Process.myUid(), context.packageName)
            } else {
                ops.checkOpNoThrow(AppOpsManager.OPSTR_GET_USAGE_STATS, Process.myUid(), context.packageName)
            }
            return mode == AppOpsManager.MODE_ALLOWED
        }
    }
}
