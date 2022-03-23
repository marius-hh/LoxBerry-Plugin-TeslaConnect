# LoxBerry-Plugin-TeslaConnect
This plugin establish a connection to Tesla API and can be used to get information or to send commands to Tesla products (e.g. vehicle, powerwall, ...). All data is transferred via MQTT. The subscription for this is `teslaconnect/#` and is automatically registered in the Loxberry MQTT gateway plugin.
## Example queries
### Returns all products including vehicles, powerwalls, and energy sites
`http://<user>:<pass>@192.168.1.1/admin/plugins/teslaconnect/tesla_command.php?action=product_list`
### Wake up vehicle
`http://<user>:<pass>@192.168.1.1/admin/plugins/teslaconnect/tesla_command.php?action=wake_up&vid=123456789`
