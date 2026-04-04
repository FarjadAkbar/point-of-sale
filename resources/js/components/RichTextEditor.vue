<script setup lang="ts">
import Editor from '@tinymce/tinymce-vue';

const model = defineModel<string>({ default: '' });

const init = {
    height: 320,
    menubar: false,
    plugins: 'lists link image table code autoresize',
    toolbar:
        'undo redo | blocks | bold italic underline strikethrough | alignleft aligncenter alignright | bullist numlist outdent indent | link image table | code removeformat',
    branding: false,
    promotion: false,
};
</script>

<template>
    <Editor
        v-model="model"
        license-key="gpl"
        tinymce-script-src="https://cdn.jsdelivr.net/npm/tinymce@7/tinymce.min.js"
        :init="init"
    />
</template>
