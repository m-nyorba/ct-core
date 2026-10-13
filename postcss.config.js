/**
 * PostCSS configuration file for Mnor Starter Theme
 *
 * This file is used to configure PostCSS with plugins for autoprefixing,
 * importing CSS files, and minifying CSS code for production.
 *
 * @package Mnor Starter
 * @see https://postcss.org/
 */

module.exports = {
  plugins: [
    require("postcss-import"),
    require("autoprefixer"),
    ...(process.env.NODE_ENV === "production"
      ? [require("cssnano")({ preset: "default" })]
      : []),
  ],
};
