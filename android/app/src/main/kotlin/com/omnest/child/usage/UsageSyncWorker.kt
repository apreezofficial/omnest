package com.omnest.child.usage

import android.content.Context
import androidx.hilt.work.HiltWorker
import androidx.work.BackoffPolicy
import androidx.work.CoroutineWorker
import androidx.work.ExistingPeriodicWorkPolicy
import androidx.work.ExistingWorkPolicy
import androidx.work.OneTimeWorkRequestBuilder
import androidx.work.PeriodicWorkRequestBuilder
import androidx.work.WorkManager
import androidx.work.WorkerParameters
import dagger.assisted.Assisted
import dagger.assisted.AssistedInject
import java.util.concurrent.TimeUnit

/**
 * Runs [UsageSync] every 15 minutes (WorkManager's minimum). No network constraint on purpose:
 * numbers are computed and stored even offline; uploads retry with backoff.
 */
@HiltWorker
class UsageSyncWorker @AssistedInject constructor(
    @Assisted context: Context,
    @Assisted params: WorkerParameters,
    private val sync: UsageSync,
) : CoroutineWorker(context, params) {

    override suspend fun doWork(): Result = when (sync.run()) {
        SyncResult.RetryLater -> if (runAttemptCount < MAX_ATTEMPTS) Result.retry() else Result.success()
        SyncResult.Unpaired -> {
            cancel(applicationContext)
            Result.success()
        }
        SyncResult.Done, SyncResult.NotReady -> Result.success()
    }

    companion object {
        private const val PERIODIC = "usage-sync"
        private const val NOW = "usage-sync-now"
        private const val MAX_ATTEMPTS = 5

        /** Safe to call often (app start, after pairing): keeps the existing schedule. */
        fun schedule(context: Context) {
            val request = PeriodicWorkRequestBuilder<UsageSyncWorker>(15, TimeUnit.MINUTES)
                .setBackoffCriteria(BackoffPolicy.EXPONENTIAL, 1, TimeUnit.MINUTES)
                .build()
            WorkManager.getInstance(context).enqueueUniquePeriodicWork(PERIODIC, ExistingPeriodicWorkPolicy.KEEP, request)
        }

        /** One extra pass right away (e.g. just paired, or the app came to the front). */
        fun runNow(context: Context) {
            val request = OneTimeWorkRequestBuilder<UsageSyncWorker>()
                .setBackoffCriteria(BackoffPolicy.EXPONENTIAL, 30, TimeUnit.SECONDS)
                .build()
            WorkManager.getInstance(context).enqueueUniqueWork(NOW, ExistingWorkPolicy.KEEP, request)
        }

        fun cancel(context: Context) {
            WorkManager.getInstance(context).cancelUniqueWork(PERIODIC)
        }
    }
}
