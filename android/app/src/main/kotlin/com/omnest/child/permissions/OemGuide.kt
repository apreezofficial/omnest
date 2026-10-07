package com.omnest.child.permissions

import android.content.ComponentName
import android.content.Context
import android.content.Intent
import android.net.Uri
import android.os.Build
import android.provider.Settings

/**
 * Many Android skins kill background apps on top of stock battery rules.
 * Brand-specific steps and the best-known settings screen for each (see dontkillmyapp.com).
 */
enum class OemGuide(
    val brandName: String,
    val steps: List<String>,
    private val screens: List<ComponentName>,
) {
    Transsion(
        brandName = "Tecno, Infinix or itel",
        steps = listOf(
            "Open Phone Master (or Settings > Apps > Auto-start management).",
            "Find Omnest and turn on Auto-start.",
            "In Recent apps, pull down on Omnest to lock it so it isn't cleared.",
        ),
        screens = listOf(
            ComponentName("com.transsion.phonemaster", "com.cyin.himgr.autostart.AutoStartActivity"),
            ComponentName("com.transsion.phonemaster", "com.cyin.himgr.applicationmanager.view.activities.AutoStartActivity"),
        ),
    ),
    Xiaomi(
        brandName = "Xiaomi, Redmi or POCO",
        steps = listOf(
            "Turn on Autostart for Omnest.",
            "In Settings > Apps > Omnest > Battery saver, choose No restrictions.",
            "In Recent apps, long-press Omnest and tap the lock.",
        ),
        screens = listOf(
            ComponentName("com.miui.securitycenter", "com.miui.permcenter.autostart.AutoStartManagementActivity"),
        ),
    ),
    Samsung(
        brandName = "Samsung",
        steps = listOf(
            "Open Settings > Battery > Background usage limits.",
            "Make sure Omnest is not in Sleeping or Deep sleeping apps.",
            "Add Omnest to Never sleeping apps.",
        ),
        screens = listOf(
            ComponentName("com.samsung.android.lool", "com.samsung.android.sm.ui.battery.BatteryActivity"),
        ),
    ),
    Oppo(
        brandName = "Oppo, Realme or OnePlus",
        steps = listOf(
            "Allow Auto launch (or Startup manager) for Omnest.",
            "In Settings > Battery > Omnest, allow background activity.",
            "In Recent apps, lock Omnest.",
        ),
        screens = listOf(
            ComponentName("com.coloros.safecenter", "com.coloros.safecenter.permission.startup.StartupAppListActivity"),
            ComponentName("com.coloros.safecenter", "com.coloros.safecenter.startupapp.StartupAppListActivity"),
            ComponentName("com.oppo.safe", "com.oppo.safe.permission.startup.StartupAppListActivity"),
        ),
    ),
    Vivo(
        brandName = "Vivo or iQOO",
        steps = listOf(
            "Allow Omnest to run in the background (Background power consumption).",
            "Turn on Autostart for Omnest in i Manager.",
            "In Recent apps, lock Omnest.",
        ),
        screens = listOf(
            ComponentName("com.vivo.permissionmanager", "com.vivo.permissionmanager.activity.BgStartUpManagerActivity"),
            ComponentName("com.iqoo.secure", "com.iqoo.secure.ui.phoneoptimize.AddWhiteListActivity"),
        ),
    );

    /** Brand screens first, then this app's info page as a safe fallback. */
    fun intents(context: Context): List<Intent> =
        screens.map { Intent().setComponent(it) } +
            Intent(Settings.ACTION_APPLICATION_DETAILS_SETTINGS, Uri.parse("package:${context.packageName}"))

    companion object {
        fun forThisPhone(manufacturer: String = Build.MANUFACTURER): OemGuide? =
            when (manufacturer.lowercase().trim()) {
                "tecno", "infinix", "itel", "tecno mobile", "infinix mobility" -> Transsion
                "xiaomi", "redmi", "poco" -> Xiaomi
                "samsung" -> Samsung
                "oppo", "realme", "oneplus" -> Oppo
                "vivo", "iqoo" -> Vivo
                else -> null
            }
    }
}
