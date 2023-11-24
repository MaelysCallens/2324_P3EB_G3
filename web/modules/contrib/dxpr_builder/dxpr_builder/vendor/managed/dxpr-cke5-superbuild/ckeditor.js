// app.js

import { ClassicEditor as ClassicEditorBase } from '@ckeditor/ckeditor5-editor-classic';
import { InlineEditor as InlineEditorBase } from '@ckeditor/ckeditor5-editor-inline';
import { Essentials } from '@ckeditor/ckeditor5-essentials';
import { Paragraph } from '@ckeditor/ckeditor5-paragraph';
import { Bold, Italic, Strikethrough, Subscript, Superscript, Underline } from '@ckeditor/ckeditor5-basic-styles';
import { RemoveFormat } from '@ckeditor/ckeditor5-remove-format';
import { Alignment } from '@ckeditor/ckeditor5-alignment';
import { Font } from '@ckeditor/ckeditor5-font';
import { Heading } from '@ckeditor/ckeditor5-heading';
import { BlockQuote } from '@ckeditor/ckeditor5-block-quote';
import { HorizontalLine } from '@ckeditor/ckeditor5-horizontal-line';
import { SourceEditing } from '@ckeditor/ckeditor5-source-editing';
import { SpecialCharacters } from '@ckeditor/ckeditor5-special-characters';
import { ShowBlocks } from '@ckeditor/ckeditor5-show-blocks';
import {
  Link,
  AutoLink
} from '@ckeditor/ckeditor5-link';
import {
  Table,
  TableCaption,
  TableCellProperties,
  TableColumnResize,
  TableProperties,
  TableToolbar
} from '@ckeditor/ckeditor5-table';
import {
  Image,
  ImageCaption,
  ImageInsert,
  ImageResize,
  ImageStyle,
  ImageToolbar,
  ImageUpload,
  AutoImage,
  PictureEditing
} from '@ckeditor/ckeditor5-image';
import {
  DocumentList,
  DocumentListProperties
} from '@ckeditor/ckeditor5-list';
import {
  Indent,
  IndentBlock
} from '@ckeditor/ckeditor5-indent';
import { Style } from '@ckeditor/ckeditor5-style';
import { GeneralHtmlSupport } from '@ckeditor/ckeditor5-html-support';


class ClassicEditor extends ClassicEditorBase {}
class InlineEditor extends InlineEditorBase {}

// Plugins to include in the build.
const plugins = [ 
  Essentials, Paragraph, Bold, Italic, Heading, Link, Strikethrough, Subscript, Superscript, Underline,
  RemoveFormat,
  Alignment,
  Font,
  BlockQuote,
  HorizontalLine,
  SourceEditing,
  SpecialCharacters,
  ShowBlocks,
  AutoLink,
  Table, TableCaption, TableCellProperties, TableColumnResize, TableProperties, TableToolbar,
  Image, ImageCaption, ImageInsert, ImageResize, ImageStyle, ImageToolbar, ImageUpload, AutoImage,
  PictureEditing,
  DocumentList, DocumentListProperties,
  Indent, IndentBlock,
  GeneralHtmlSupport, Style,
];

ClassicEditor.builtinPlugins = plugins;
InlineEditor.builtinPlugins = plugins;

// Editor default configuration.
const config = {
  // ...
};

ClassicEditor.defaultConfig = config;
InlineEditor.defaultConfig = config;

export default {
  ClassicEditor, InlineEditor
};

