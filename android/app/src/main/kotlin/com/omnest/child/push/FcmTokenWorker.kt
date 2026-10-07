package com.omnest.child.push

import android.content.Context
import androidx.hilt.work.HiltWorker
import androidx.work.BackoffPolicy
import androidx.work.Constraints
import androidx.work.CoroutineWorker
import androidx.work.ExistingWorkPolicy
import androidx.work.NetworkType
import androidx.work.OneTimeWorkRequestBuilder
import androidx.work.WorkManager
import androidx.work.WorkerParameters
import androidx.work.workDataOf
import com.omnest.child.data.DeviceSession
import com.omnest.child.data.api.FcmTokenRequest
import com.omnest.child.data.api.OmnestApi
import dagger.assisted.Assisted
import dagger.assisted.AssistedInject
import java.io.IOException
import java.util.concurrent.TimeUnit

/** Sends a new FCM token to the API. Retries with backoff until the phone is online. */
@HiltWorker
class FcmTokenWorker @AssistedInject constructor(
    @Assisted context: Context,
    @Assisted params: WorkerParameters,
    private val api: OmnestApi,
    private val session: DeviceSession,
) : CoroutineWorker(context, params) {

    override suspend fun doWork(): Result {
        val auth = session.authHeader ?: return Result.success() // not paired yet; pairing sends the token
        val token = inputData.getString(KEY_TOKEN) ?: return Result.failure()

        return try {
            val res = api.updateFcmToken(auth, FcmTokenRequest(token))
            when {
                res.isSuccessful -> Result.success()
                res.code() == 401 -> Result.failure() // unpaired by the parent
                else -> Result.retry()
            }
        } catch (_: IOException) {
            Result.retry()
        }
    }

    companion object {
        private const val KEY_TOKEN = "fcm_token"
        private const val UNIQUE_NAME = "fcm-token-upload"

        fun enqueue(context: Context, token: String) {
            val request = OneTimeWorkRequestBuilder<FcmTokenWorker>()
                .setInputData(workDataOf(KEY_TOKEN to token))
                .setConstraints(Constraints.Builder().setRequiredNetworkType(NetworkType.CONNECTED).build())
                .setBackoffCriteria(BackoffPolicy.EXPONENTIAL, 30, TimeUnit.SECONDS)
                .build()
            WorkManager.getInstance(context).enqueueUniqueWork(UNIQUE_NAME, ExistingWorkPolicy.REPLACE, request)
        }
    }
}
