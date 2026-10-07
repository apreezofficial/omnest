package com.omnest.child

import android.app.Application
import androidx.hilt.work.HiltWorkerFactory
import androidx.work.Configuration
import com.omnest.child.data.DeviceSession
import com.omnest.child.usage.UsageSyncWorker
import dagger.hilt.android.HiltAndroidApp
import javax.inject.Inject

@HiltAndroidApp
class OmnestApp : Application(), Configuration.Provider {

    @Inject lateinit var workerFactory: HiltWorkerFactory
    @Inject lateinit var session: DeviceSession

    override val workManagerConfiguration: Configuration
        get() = Configuration.Builder().setWorkerFactory(workerFactory).build()

    override fun onCreate() {
        super.onCreate()
        // WorkManager keeps the schedule across reboots; this re-arms it if it was ever cancelled.
        if (session.current.value != null) UsageSyncWorker.schedule(this)
    }
}
