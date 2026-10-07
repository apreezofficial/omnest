package com.omnest.child.data.api

import com.omnest.child.data.ApiResponse
import kotlinx.serialization.SerialName
import kotlinx.serialization.Serializable
import retrofit2.Response
import retrofit2.http.Body
import retrofit2.http.GET
import retrofit2.http.Header
import retrofit2.http.POST
import retrofit2.http.PUT

/** Device-side endpoints of the Omnest API (backend/routes/api.php, "Child phone"). */
interface OmnestApi {

    @POST("device/pair")
    suspend fun pair(@Body body: PairRequest): Response<ApiResponse<PairResult>>

    @GET("device/me")
    suspend fun me(@Header("Authorization") auth: String): Response<ApiResponse<DeviceMe>>

    @PUT("device/fcm-token")
    suspend fun updateFcmToken(@Header("Authorization") auth: String, @Body body: FcmTokenRequest): Response<Unit>

    @POST("device/usage")
    suspend fun uploadUsage(@Header("Authorization") auth: String, @Body body: UsageUpload): Response<ApiResponse<UsageAccepted>>

    @PUT("device/apps")
    suspend fun syncApps(@Header("Authorization") auth: String, @Body body: AppsUpload): Response<Unit>
}

@Serializable
data class UsageUpload(val timezone: String, val days: List<UsageDayDto>)

@Serializable
data class UsageDayDto(val date: String, val apps: List<AppSecondsDto>)

@Serializable
data class AppSecondsDto(@SerialName("package") val packageName: String, val seconds: Int)

@Serializable
data class UsageAccepted(val accepted: List<String>)

@Serializable
data class AppsUpload(val apps: List<InstalledAppDto>)

@Serializable
data class InstalledAppDto(
    @SerialName("package") val packageName: String,
    val label: String,
    val system: Boolean,
)

@Serializable
data class PairRequest(
    val code: String,
    val name: String,
    val model: String?,
    @SerialName("os_version") val osVersion: String?,
    @SerialName("app_version") val appVersion: String?,
    @SerialName("fcm_token") val fcmToken: String?,
)

@Serializable
data class PairResult(
    val token: String,
    val device: DeviceDto,
    val child: ChildDto,
)

@Serializable
data class DeviceMe(
    val device: DeviceDto,
    val child: ChildDto,
)

@Serializable
data class DeviceDto(
    val id: Long,
    @SerialName("child_id") val childId: Long,
    val name: String,
)

@Serializable
data class ChildDto(
    val id: Long,
    val name: String,
    @SerialName("age_tier") val ageTier: String,
)

@Serializable
data class FcmTokenRequest(@SerialName("fcm_token") val fcmToken: String)
