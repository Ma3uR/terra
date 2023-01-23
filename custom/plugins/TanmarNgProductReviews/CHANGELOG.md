# 1.0.0
- first version for shopware 6

# 1.0.1
- Fixed a bug where the rating without stars triggers an error

# 1.0.2
- Fixed a bug in the config reader

# 1.0.3
- fixed a bug where old customers would not reviece mails

# 1.0.4
- fixed another bug where old customers would not reviece mails

# 1.0.5
- Improved error handling

# 1.0.6
- added "headline is required" configuration
- Bugfix order number with prefix or suffix

# 1.0.7
- Bugfix Mail Templates

# 1.0.8
- Bugfix controller template menu bar was not displayed

# 1.0.9
- Fixed a bug where already rated products are not visible

# 1.0.10
- Showpare 6.4 compatibility

# 1.1.0
- Added test mail buttons in configuration
- Added option to recieve blind copies of invitation mails
- Added option to ignore orders before a certain date
- Added mail notification when a customer has completed writing reviews for an order

# 1.1.1
- Products that have already been rated are displayed last on the plugin rating page

# 1.2.0
- Added option to send voucher mails
- Added test voucher mail buttons in configuration
- Attention: If {{salesChannel.name}} no longer works in your mail templates, then change to: {{salesChannel.translated.name}}
- If the voucher mails are not sent, please check the mail templates and delete the following variable: {{order.orderCustomer.salutation.letterName}}