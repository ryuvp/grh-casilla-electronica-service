<template>
  <ckeditor
    v-model="data"
    :editor="ClassicEditor"
    :config="config"
  />
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { Ckeditor } from '@ckeditor/ckeditor5-vue'

// Plugins de CKEditor (asegúrate de tener `ckeditor5` configurado correctamente)
import {
  ClassicEditor,
  Alignment,
  AutoImage,
  Base64UploadAdapter,
  BlockQuote,
  Bold,
  Code,
  CodeBlock,
  Essentials,
  FindAndReplace,
  FontBackgroundColor,
  FontColor,
  FontFamily,
  FontSize,
  Heading,
  Highlight,
  HorizontalLine,
  Image,
  ImageCaption,
  ImageInsert,
  ImageResize,
  ImageStyle,
  ImageToolbar,
  ImageUpload,
  Indent,
  IndentBlock,
  Italic,
  Link,
  List,
  ListProperties,
  MediaEmbed,
  Mention,
  PageBreak,
  Paragraph,
  PasteFromOffice,
  RemoveFormat,
  SourceEditing,
  SpecialCharacters,
  SpecialCharactersEssentials,
  Strikethrough,
  Subscript,
  Superscript,
  Table,
  TableToolbar,
  TableProperties,
  TableCellProperties,
  TableColumnResize,
  TextTransformation,
  TodoList,
  Underline,
  Undo,
  WordCount
} from 'ckeditor5'

import 'ckeditor5/ckeditor5.css'

// Props y emisión del modelo
const props = defineProps({
  modelValue : {
    type    : String,
    default : ''
  }
})
const emit = defineEmits(['update:modelValue'])

// Estado local sincronizado
const data = ref(props.modelValue)

watch(() => props.modelValue, (val) => {
  if (val !== data.value) {
    data.value = val
  }
})

watch(data, (val) => {
  if (val !== props.modelValue) {
    emit('update:modelValue', val)
  }
})

// Configuración del editor
const config = computed(() => ({
  licenseKey : 'GPL',
  language   : 'es',
  plugins    : [
    // Core y básicos
    Essentials, Paragraph, Heading, Undo,

    // Estilos y texto
    Bold, Italic, Underline, Strikethrough, Code, Subscript, Superscript,
    FontFamily, FontSize, FontColor, FontBackgroundColor, Highlight,
    RemoveFormat, TextTransformation,

    // Listas y estructura
    List, ListProperties, TodoList, Indent, IndentBlock,

    // Enlaces, medios y código
    Link, BlockQuote, CodeBlock, MediaEmbed, Mention,

    // Imágenes
    Image, ImageUpload, ImageToolbar, ImageStyle, ImageResize, ImageInsert,
    ImageCaption, AutoImage, Base64UploadAdapter,

    // Tablas
    Table, TableToolbar, TableProperties, TableCellProperties, TableColumnResize,

    // Utilidades
    Alignment, HorizontalLine, PageBreak, PasteFromOffice,
    FindAndReplace, SourceEditing, WordCount, SpecialCharacters, SpecialCharactersEssentials
  ],
  toolbar : {
    items : [
      'undo', 'redo',
      '|',
      'heading',
      '|',
      'fontfamily', 'fontsize', 'fontColor', 'fontBackgroundColor',
      '|',
      'bold', 'italic', 'strikethrough', 'underline', 'code',
      '|',
      'link', 'blockQuote', 'codeBlock',
      '|',
      'todoList', 'indent', 'insertTable',
    ],
    shouldNotGroupWhenFull : false
  },
  table : {
    contentToolbar : [
      'tableColumn', 'tableRow', 'mergeTableCells',
      'tableProperties', 'tableCellProperties'
    ]
  },
  image : {
    toolbar : [
      'imageTextAlternative',
      '|',
      'imageStyle:inline',
      'imageStyle:block',
      'imageStyle:side'
    ]
  }
}))
</script>

<style>
.ck-editor__editable_inline {
  min-height: 600px !important;
  max-height: 600px !important;
  overflow-y: auto !important;
  padding: 1rem;
  background-color: #fff;
  border: 1px solid #ddd;
  border-radius: 6px;
}
</style>
