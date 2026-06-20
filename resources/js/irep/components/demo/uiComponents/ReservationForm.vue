<script setup lang="ts">
import { ref, watch } from "vue";
import Input from "../form/Input.vue";
import Button from "./Button.vue";
import SuccessCheck from "../../icons/SuccessCheck.vue";
import createAxios from "../../../utils/axios";
import { useGlobalStore } from "../../../store/useGlobal";
import type { FlatItem } from "../../../types/DemoTypes";
import { useTr } from "../../../composable/helper";

const emit = defineEmits<{
    (e: "hideForm"): void;
}>();

const props = defineProps<{
    flat?: FlatItem;
}>();

const globalStore = useGlobalStore();
const tr = useTr();

const obj = ref({
    project_id: props.flat?.project_id,
    flat_id: props.flat?.id,
    name: "",
    phone: "",
    email: "",
    comment: "",
});

const loading = ref(false);
const isSuccess = ref(false);
const commentFocused = ref(false);

const errors = ref({ name: "", phone: "", email: "" });
const submitted = ref(false);

const rules = {
    name: (v: string) => {
        if (!v.trim()) return tr("Name is required");
        if (v.trim().length < 2)
            return tr("Name must be at least 2 characters");
        return "";
    },
    phone: (v: string) => {
        if (!v.trim()) return tr("Phone is required");
        if (!/^[+\d][\d\s\-(). ]{5,20}$/.test(v.trim()))
            return tr("Enter a valid phone number");
        return "";
    },
    email: (v: string) => {
        if (!v.trim()) return tr("Email is required");
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v.trim()))
            return tr("Enter a valid email address");
        return "";
    },
};

watch(
    () => obj.value.name,
    (v) => {
        if (submitted.value) errors.value.name = rules.name(v);
    },
);
watch(
    () => obj.value.phone,
    (v) => {
        if (submitted.value) errors.value.phone = rules.phone(v);
    },
);
watch(
    () => obj.value.email,
    (v) => {
        if (submitted.value) errors.value.email = rules.email(v);
    },
);

const validate = () => {
    errors.value.name = rules.name(obj.value.name);
    errors.value.phone = rules.phone(obj.value.phone);
    errors.value.email = rules.email(obj.value.email);
    return !errors.value.name && !errors.value.phone && !errors.value.email;
};

const handleSubmit = async () => {
    submitted.value = true;
    if (!validate()) return;
    if (loading.value) return;

    loading.value = true;

    const { data } = await createAxios(
        globalStore.irePlaginWp?.ajax_url ?? "",
    ).post("", {
        action: "irep_create_reservation",
        nonce: globalStore.irePlaginWp?.nonce,
        ...obj.value,
    });

    if (data?.success) {
        setTimeout(() => {
            loading.value = false;
            isSuccess.value = true;
            obj.value = {
                ...obj.value,
                name: "",
                phone: "",
                email: "",
                comment: "",
            };
        }, 300);
        setTimeout(() => {
            isSuccess.value = false;
            submitted.value = false;
            errors.value = { name: "", phone: "", email: "" };
        }, 8000);
    } else {
        loading.value = false;

        console.error("Something went wrong!");
        // emit("hideForm");
    }
};
</script>
<template>
    <form
        @submit.prevent="handleSubmit"
        class="irep-reservation-form ire-w-full ire-p-2"
    >
        <Transition name="ire-fade-in-out" mode="out-in">
            <div
                v-if="isSuccess"
                class="irep-reservation-form__success ire-flex ire-flex-col ire-items-center ire-justify-center ire-text-center"
            >
                <SuccessCheck class="ire-success-icon" />

                <div
                    class="ire-success-title ire-text-xl ire-font-semibold ire-text-black"
                >
                    {{ tr("Thank you!") }}
                </div>
                <p class="ire-success-text ire-text-sm ire-text-gray-500">
                    {{
                        tr(
                            "Your request has been received. Our team will reach out to you shortly.",
                        )
                    }}
                </p>
            </div>

            <div
                v-else
                class="irep-callback-form ire-flex ire-flex-col ire-gap-4"
            >
                <div
                    class="irep-callback-form__title ire-mb-4 ire-text-center ire-text-xl ire-font-semibold ire-text-black"
                >
                    {{ tr("Request callback") }}
                </div>

                <Input v-model="obj.name" label="Name" :error="errors.name" />

                <Input
                    v-model="obj.phone"
                    label="Phone"
                    :error="errors.phone"
                />

                <Input
                    v-model="obj.email"
                    type="email"
                    label="Email"
                    :error="errors.email"
                />

                <div
                    class="irep-reservation-form__comment ire-relative ire-w-full ire-text-black"
                >
                    <textarea
                        v-model="obj.comment"
                        rows="3"
                        @focus="commentFocused = true"
                        @blur="commentFocused = false"
                        class="ire-w-full ire-rounded-md ire-border-none ire-px-3 ire-pb-2 ire-pt-5 ire-text-base ire-outline-none ire-ring-[1px] ire-ring-gray-200 ire-transition-all focus:ire-ring-black"
                    />
                    <span
                        class="ire-pointer-events-none ire-absolute ire-left-3 ire-text-gray-600 ire-transition-all ire-duration-200"
                        :class="
                            commentFocused || obj.comment
                                ? 'ire-top-1.5 ire-text-xs'
                                : 'ire-top-3 ire-text-base'
                        "
                    >
                        {{ tr("Comment") }}
                    </span>
                </div>
            </div>
        </Transition>

        <Transition name="ire-fade-in-out" mode="out-in">
            <div
                v-if="!isSuccess"
                class="irep-callback-form__buttons ire-mt-4 ire-flex ire-flex-wrap ire-gap-3"
            >
                <Button
                    type="button"
                    title="back"
                    class="ire-flex-1"
                    variant="outline"
                    @click="emit('hideForm')"
                />

                <Button
                    title="Submit"
                    class="ire-flex-1"
                    :disable="loading || isSuccess"
                    @click="handleSubmit"
                />
            </div>
        </Transition>
    </form>
</template>

<style scoped>
.irep-reservation-form__success {
    padding: 2.5rem 1.5rem;
}

.ire-success-icon {
    margin-bottom: 1.5rem;
}

.ire-success-title {
    margin-bottom: 0.5rem;
    opacity: 0;
    transform: translateY(8px);
    animation: ire-rise 0.5s ease-out 0.7s forwards;
}

.ire-success-text {
    max-width: 20rem;
    line-height: 1.625;
    opacity: 0;
    transform: translateY(8px);
    animation: ire-rise 0.5s ease-out 0.82s forwards;
}

@keyframes ire-rise {
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@media (prefers-reduced-motion: reduce) {
    .ire-success-title,
    .ire-success-text {
        animation-duration: 0.01ms;
        animation-delay: 0ms;
    }
}
</style>
