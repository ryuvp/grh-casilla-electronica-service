// eslint.config.js (CommonJS)
import js from "@eslint/js";
import vue from "eslint-plugin-vue";

export default [
  js.configs.recommended,
  ...vue.configs["flat/recommended"],
  {
    files           : ["**/*.vue", "**/*.js"],
    languageOptions : {
      ecmaVersion : "latest",
      sourceType  : "module",
      globals     : {
        console        : "readonly",
        window         : "readonly",
        document       : "readonly",
        setTimeout     : "readonly",
        setInterval    : "readonly",
        clearTimeout   : "readonly",
        clearInterval  : "readonly",
        localStorage   : "readonly",
        sessionStorage : "readonly",
        hashMd5        : "readonly",
        __dirname      : "readonly",
        URL            : "readonly",
        FormData       : "readonly",
        Blob           : "readonly",
        File           : "readonly",
        // Agrega aquí cualquier otra global que uses
      }
    },
    plugins : { vue },
    rules   : {
      "indent"      : ["error", 2],
      "key-spacing" : [
        "error",
        {
          "singleLine" : { beforeColon: false, afterColon: true },
          "multiLine"  : { beforeColon: true, afterColon: true, align: "colon" }
        }
      ],
      "vue/max-attributes-per-line" : [
        "error",
        {
          "singleline" : { "max": 5 },
          "multiline"  : { "max": 1 }
        }
      ],
      "vue/html-self-closing" : [
        "error",
        {
          "html" : { "void": "always", "normal": "never", "component": "always" },
          "svg"  : "always",
          "math" : "always"
        }
      ],
      "vue/multi-word-component-names"              : "off",
      "vue/no-mutating-props"                       : "off",
      "vue/singleline-html-element-content-newline" : "off",
      "vue/multiline-html-element-content-newline"  : "off",
      "vue/first-attribute-linebreak"               : [
        "error",
        {
          "singleline" : "ignore",
          "multiline"  : "below"
        }
      ]
    }
  }
];
