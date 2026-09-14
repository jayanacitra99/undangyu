<script setup>
/*
| Repeatable feature-flag rows for the package form (M2.2).
|
| An island over a set of plain inputs: every row posts as
| features[n][feature_key] / [feature_value] / [is_unlimited], so the form works
| as an ordinary POST and the server validates it in a FormRequest.
|
| The key list comes from App\Enums\FeatureKey — the component never invents one.
*/
import { computed, ref } from 'vue';

const props = defineProps({
    featureKeys: { type: Array, required: true },
    rows: { type: Array, default: () => [] },
    errors: { type: Object, default: () => ({}) },
});

const items = ref(
    props.rows.map((row) => ({
        feature_key: row.feature_key,
        feature_value: row.feature_value ?? '',
        is_unlimited: Boolean(row.is_unlimited),
    })),
);

const usedKeys = computed(() => items.value.map((item) => item.feature_key));

const availableKeys = computed(() =>
    props.featureKeys.filter((key) => !usedKeys.value.includes(key.value)),
);

const isQuota = (value) => props.featureKeys.find((key) => key.value === value)?.is_quota ?? false;

const errorFor = (index, field) => props.errors[`features.${index}.${field}`]?.[0] ?? null;

function addRow() {
    const next = availableKeys.value[0];

    if (!next) {
        return;
    }

    items.value.push({ feature_key: next.value, feature_value: '', is_unlimited: false });
}

function removeRow(index) {
    items.value.splice(index, 1);
}
</script>

<template>
    <div>
        <p v-if="items.length === 0" class="text-secondary">
            Paket ini belum punya fitur. Tambahkan minimal satu.
        </p>

        <div v-for="(item, index) in items" :key="index" class="row g-2 align-items-end mb-2">
            <div class="col-md-5">
                <label :for="`feature_key_${index}`" class="form-label">Fitur</label>
                <select
                    :id="`feature_key_${index}`"
                    v-model="item.feature_key"
                    :name="`features[${index}][feature_key]`"
                    class="form-select"
                    :class="{ 'is-invalid': errorFor(index, 'feature_key') }"
                >
                    <option v-for="key in featureKeys" :key="key.value" :value="key.value"
                            :disabled="key.value !== item.feature_key && usedKeys.includes(key.value)">
                        {{ key.label }}
                    </option>
                </select>
                <div v-if="errorFor(index, 'feature_key')" class="invalid-feedback">
                    {{ errorFor(index, 'feature_key') }}
                </div>
            </div>

            <div class="col-md-4">
                <label :for="`feature_value_${index}`" class="form-label">Nilai</label>
                <input
                    :id="`feature_value_${index}`"
                    v-model="item.feature_value"
                    :name="`features[${index}][feature_value]`"
                    type="number"
                    min="0"
                    class="form-control"
                    :class="{ 'is-invalid': errorFor(index, 'feature_value') }"
                    :disabled="!isQuota(item.feature_key) || item.is_unlimited"
                    :placeholder="isQuota(item.feature_key) ? 'Angka' : 'Aktif / nonaktif saja'"
                >
                <div v-if="errorFor(index, 'feature_value')" class="invalid-feedback">
                    {{ errorFor(index, 'feature_value') }}
                </div>
            </div>

            <div class="col-md-2">
                <div class="form-check form-switch">
                    <input type="hidden" :name="`features[${index}][is_unlimited]`" value="0">
                    <input
                        :id="`is_unlimited_${index}`"
                        v-model="item.is_unlimited"
                        :name="`features[${index}][is_unlimited]`"
                        type="checkbox"
                        value="1"
                        class="form-check-input"
                    >
                    <label class="form-check-label" :for="`is_unlimited_${index}`">
                        {{ isQuota(item.feature_key) ? 'Tanpa batas' : 'Aktif' }}
                    </label>
                </div>
            </div>

            <div class="col-md-1 text-end">
                <button type="button" class="btn btn-sm btn-outline-danger" @click="removeRow(index)">
                    <i class="bi bi-trash"></i>
                    <span class="visually-hidden">Hapus fitur</span>
                </button>
            </div>
        </div>

        <button type="button" class="btn btn-sm btn-outline-primary mt-2"
                :disabled="availableKeys.length === 0" @click="addRow">
            <i class="bi bi-plus-lg"></i> Tambah fitur
        </button>
    </div>
</template>
