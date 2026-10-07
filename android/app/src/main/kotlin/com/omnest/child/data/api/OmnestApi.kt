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
}

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
