// const { getDefaultConfig, mergeConfig } = require('@react-native/metro-config');

// /**
//  * Metro configuration for React Native / Expo prebuild
//  * https://reactnative.dev/docs/metro
//  * @type {import('metro-config').MetroConfig}
//  */
// const defaultConfig = getDefaultConfig(__dirname);

// const config = {
//   projectRoot: __dirname,
// };

// module.exports = mergeConfig(defaultConfig, config);
// metro.config.js (Expo)
const { getDefaultConfig } = require('expo/metro-config');

const config = getDefaultConfig(__dirname);

module.exports = config;