package com.omnest.child.data.db

import androidx.room.Dao
import androidx.room.Database
import androidx.room.Entity
import androidx.room.Query
import androidx.room.RoomDatabase
import androidx.room.Transaction
import androidx.room.Upsert

/** Foreground seconds for one app on one local day (yyyy-MM-dd). */
@Entity(tableName = "app_usage", primaryKeys = ["date", "packageName"])
data class AppUsageEntity(
    val date: String,
    val packageName: String,
    val seconds: Int,
)

/** When a day was last computed and last accepted by the server. */
@Entity(tableName = "day_sync", primaryKeys = ["date"])
data class DaySyncEntity(
    val date: String,
    val computedAt: Long,
    val syncedAt: Long? = null,
)

@Dao
abstract class UsageDao {

    /** Replaces a day's numbers with a fresh computation. */
    @Transaction
    open suspend fun replaceDay(date: String, seconds: Map<String, Int>, computedAt: Long) {
        deleteDay(date)
        insertAll(seconds.map { (pkg, s) -> AppUsageEntity(date, pkg, s) })
        val previous = syncFor(date)
        upsertSync(DaySyncEntity(date, computedAt, previous?.syncedAt))
    }

    @Query("DELETE FROM app_usage WHERE date = :date")
    abstract suspend fun deleteDay(date: String)

    @Upsert
    abstract suspend fun insertAll(rows: List<AppUsageEntity>)

    @Query("SELECT * FROM app_usage WHERE date = :date ORDER BY seconds DESC")
    abstract suspend fun day(date: String): List<AppUsageEntity>

    @Query("SELECT COALESCE(SUM(seconds), 0) FROM app_usage WHERE date = :date")
    abstract suspend fun totalSeconds(date: String): Int

    @Query("SELECT * FROM day_sync WHERE date = :date")
    abstract suspend fun syncFor(date: String): DaySyncEntity?

    @Upsert
    abstract suspend fun upsertSync(row: DaySyncEntity)

    @Query("SELECT COUNT(*) FROM day_sync")
    abstract suspend fun dayCount(): Int

    /** Days computed since they were last uploaded, newest first. */
    @Query("SELECT * FROM day_sync WHERE syncedAt IS NULL OR syncedAt < computedAt ORDER BY date DESC LIMIT :limit")
    abstract suspend fun pending(limit: Int): List<DaySyncEntity>

    @Query("UPDATE day_sync SET syncedAt = :at WHERE date IN (:dates)")
    abstract suspend fun markSynced(dates: List<String>, at: Long)

    @Query("DELETE FROM app_usage WHERE date < :before")
    abstract suspend fun purgeUsage(before: String)

    @Query("DELETE FROM day_sync WHERE date < :before")
    abstract suspend fun purgeSync(before: String)
}

@Database(entities = [AppUsageEntity::class, DaySyncEntity::class], version = 1, exportSchema = true)
abstract class OmnestDatabase : RoomDatabase() {
    abstract fun usageDao(): UsageDao
}
