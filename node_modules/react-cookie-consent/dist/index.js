
'use strict'

if (process.env.NODE_ENV === 'production') {
  module.exports = require('./react-cookie-consent.cjs.production.min.js')
} else {
  module.exports = require('./react-cookie-consent.cjs.development.js')
}
