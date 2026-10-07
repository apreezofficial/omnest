package com.omnest.child.data

import kotlinx.serialization.Serializable
import kotlinx.serialization.json.Json
import kotlinx.serialization.json.JsonObject
import kotlinx.serialization.json.jsonArray
import kotlinx.serialization.json.jsonObject
import kotlinx.serialization.json.jsonPrimitive
import okhttp3.ResponseBody

/** Mirrors the backend's standard JSON format (backend/README.md). */
@Serializable
data class ApiResponse<T>(val data: T)

@Serializable
data class ApiErrorBody(val error: ApiError)

@Serializable
data class ApiError(
    val code: String,
    val message: String,
    val details: JsonObject? = null,
) {
    /** First message for a field from a 422 validation error, e.g. fieldError("code"). */
    fun fieldError(field: String): String? = runCatching {
        details?.get("fields")?.jsonObject?.get(field)?.jsonArray?.firstOrNull()?.jsonPrimitive?.content
    }.getOrNull()
}

/** Parses an error body, or null if it isn't in the standard format. */
fun ResponseBody?.toApiError(json: Json): ApiError? = this?.let {
    runCatching { json.decodeFromString<ApiErrorBody>(it.string()).error }.getOrNull()
}
