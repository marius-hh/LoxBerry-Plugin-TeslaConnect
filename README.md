# LoxBerry-Plugin-TeslaConnect
This plugin establish a connection to Tesla-API and can be used to get information or to send commands to the vehicle. All data is transferred via MQTT. The subscription for this is teslaconnect/# and is automatically registered in the Loxberry MQTT gateway plugin.
## Example queries
### Get Summary of all vehicles
`http://<user>:<pass>@192.168.1.1/admin/plugins/teslaconnect/tesla_command.php?action=summary`
### Wake up vehicle
`http://<user>:<pass>@192.168.1.1/admin/plugins/teslaconnect/tesla_command.php?action=wake_up&vid=123456789`
