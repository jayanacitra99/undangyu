<script setup>
/*
| The gallery (M4.6, 22.4).
|
| Swiper for the carousel, and every slide carries an explicit aspect ratio
| plus a `srcset` of the WebP renditions ProcessMediaJob made. The ratio is
| what keeps the layout from shifting as images arrive; the srcset is what
| stops a phone downloading a 2000px photo to fill 360 CSS pixels.
|
| Videos are embeds, so they get a poster frame and load their iframe only
| when tapped — an autoplaying YouTube iframe per video would cost more than
| the rest of the page combined.
*/
import { ref } from 'vue';
import { Swiper, SwiperSlide } from 'swiper/vue';
// No Lazy module: Swiper dropped it in v11 in favour of the browser's own
// `loading="lazy"`, which every slide below uses.
import { A11y, Keyboard, Navigation, Pagination } from 'swiper/modules';
import 'swiper/css';
import 'swiper/css/navigation';
import 'swiper/css/pagination';

const props = defineProps({
    heading: { type: String, default: '' },
    media: { type: Array, required: true },
});

const photos = props.media.filter((item) => item.type === 'image');
const videos = props.media.filter((item) => item.type === 'video');

const playing = ref(null);
const modules = [Navigation, Pagination, Keyboard, A11y];

const lightbox = ref(null);
</script>

<template>
    <section class="py-16">
        <h2 class="reveal px-6 text-center font-heading text-3xl">{{ heading }}</h2>

        <div v-if="photos.length" class="reveal mt-10">
            <Swiper
                :modules="modules"
                :slides-per-view="1.15"
                :space-between="12"
                :centered-slides="true"
                :keyboard="{ enabled: true }"
                :pagination="{ clickable: true }"
                :breakpoints="{ 640: { slidesPerView: 2.2 }, 1024: { slidesPerView: 3.2 } }"
                class="px-6"
            >
                <SwiperSlide v-for="photo in photos" :key="photo.url">
                    <figure class="overflow-hidden rounded-2xl">
                        <img
                            :src="photo.medium"
                            :srcset="`${photo.thumb} 400w, ${photo.medium} 1000w, ${photo.full} 2000w`"
                            sizes="(min-width: 1024px) 30vw, (min-width: 640px) 45vw, 85vw"
                            :alt="photo.caption ?? ''"
                            loading="lazy"
                            decoding="async"
                            class="aspect-[3/4] w-full cursor-zoom-in object-cover"
                            @click="lightbox = photo"
                        >
                        <figcaption v-if="photo.caption" class="mt-2 px-1 font-body text-xs opacity-70">
                            {{ photo.caption }}
                        </figcaption>
                    </figure>
                </SwiperSlide>
            </Swiper>
        </div>

        <div v-if="videos.length" class="mx-auto mt-10 max-w-2xl space-y-4 px-6">
            <div v-for="video in videos" :key="video.embed_url" class="reveal overflow-hidden rounded-2xl bg-black/5">
                <iframe
                    v-if="playing === video.embed_url"
                    :src="`${video.embed_url}?autoplay=1`"
                    title="Video"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                    allowfullscreen
                    class="aspect-video w-full"
                ></iframe>

                <button
                    v-else
                    type="button"
                    class="relative block aspect-video w-full"
                    @click="playing = video.embed_url"
                >
                    <img
                        v-if="video.thumb"
                        :src="video.thumb"
                        :alt="video.caption ?? 'Video'"
                        loading="lazy"
                        decoding="async"
                        class="h-full w-full object-cover"
                    >
                    <span class="absolute inset-0 flex items-center justify-center">
                        <span class="rounded-full bg-white/90 px-5 py-3 font-body text-sm">▶ Putar video</span>
                    </span>
                </button>
            </div>
        </div>

        <!-- A tapped photo opens full size. Closing is a tap anywhere, which is
             what a guest's thumb expects. -->
        <div
            v-if="lightbox"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/90 p-4"
            @click="lightbox = null"
        >
            <img :src="lightbox.full" :alt="lightbox.caption ?? ''" class="max-h-full max-w-full object-contain">
        </div>
    </section>
</template>
