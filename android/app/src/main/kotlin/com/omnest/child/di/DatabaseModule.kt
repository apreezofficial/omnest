package com.omnest.child.di

import android.content.Context
import androidx.room.Room
import com.omnest.child.data.db.OmnestDatabase
import com.omnest.child.data.db.UsageDao
import dagger.Module
import dagger.Provides
import dagger.hilt.InstallIn
import dagger.hilt.android.qualifiers.ApplicationContext
import dagger.hilt.components.SingletonComponent
import javax.inject.Singleton

@Module
@InstallIn(SingletonComponent::class)
object DatabaseModule {

    @Provides
    @Singleton
    fun database(@ApplicationContext context: Context): OmnestDatabase =
        Room.databaseBuilder(context, OmnestDatabase::class.java, "omnest.db").build()

    @Provides
    fun usageDao(db: OmnestDatabase): UsageDao = db.usageDao()
}
