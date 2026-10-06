# kotlinx.serialization: keep generated serializers for @Serializable DTOs.
-keepattributes *Annotation*, InnerClasses
-keepclassmembers @kotlinx.serialization.Serializable class com.omnest.child.** {
    *** Companion;
    kotlinx.serialization.KSerializer serializer(...);
}

# Retrofit service interfaces are used via reflection.
-keep,allowobfuscation interface com.omnest.child.data.**Api
