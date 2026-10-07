package com.omnest.child.usage

import android.content.Context
import android.content.Intent
import android.content.pm.ApplicationInfo
import android.content.pm.PackageManager
import com.omnest.child.data.api.InstalledAppDto
import dagger.hilt.android.qualifiers.ApplicationContext
import java.security.MessageDigest
import javax.inject.Inject
import javax.inject.Singleton

/** Apps that show up in the launcher (what a parent would think of as "apps on the phone"). */
@Singleton
class AppInventory @Inject constructor(@ApplicationContext private val context: Context) {

    fun launchableApps(): List<InstalledAppDto> {
        val pm = context.packageManager
        val launcher = Intent(Intent.ACTION_MAIN).addCategory(Intent.CATEGORY_LAUNCHER)

        return pm.queryIntentActivities(launcher, PackageManager.MATCH_ALL)
            .map { it.activityInfo.applicationInfo }
            .distinctBy { it.packageName }
            .filter { it.packageName != context.packageName }
            .map { info ->
                InstalledAppDto(
                    packageName = info.packageName,
                    label = info.loadLabel(pm).toString().trim().take(120).ifEmpty { info.packageName },
                    system = info.flags and ApplicationInfo.FLAG_SYSTEM != 0,
                )
            }
            .sortedBy { it.packageName }
    }

    /** Fingerprint of the list, so we only upload when something was installed, removed or renamed. */
    fun fingerprint(apps: List<InstalledAppDto>): String {
        val digest = MessageDigest.getInstance("SHA-256")
        apps.forEach { digest.update("${it.packageName}|${it.label}|${it.system}\n".toByteArray()) }
        return digest.digest().joinToString("") { "%02x".format(it) }
    }
}
