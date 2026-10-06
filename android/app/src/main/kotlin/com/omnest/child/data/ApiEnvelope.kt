package com.omnest.child.data

import kotlinx.serialization.Serializable
import kotlinx.serialization.json.JsonObject

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
)
