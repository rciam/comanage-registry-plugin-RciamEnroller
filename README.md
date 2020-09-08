# comanage-registry-plugin-RciamEnroller
The Rciam[Enroller COmanage Plugin](https://spaces.at.internet2.edu/display/COmanage/Enrollment+Flow+Plugins) will check the existence of any valid Certificate of a user prior to its
Enrollment in the configured COU/VO 


## Installation

1. Run `git clone https://github.com/rciam/comanage-registry-plugin-RciamEnroller.git /path/to/comanage/local/Plugin/RciamEnroller`
2. Run `cd /path/to/comanage/app`
3. Run `Console/clearcache`
4. Run `Console/cake schema create --file schema.php --path /path/to/comanage/local/Plugin/RciamEnroller/Config/Schema`

## Schema update
Not yet implemented
 
## Configuration

After the installation, you have to configure the plugin before using it. 
1. Navigate to Configuration > RCIAM Enroller
2. Configure the plugin
   * Status: Active
   * Enrollment Flow: Pick from the dropdown list the Enrollment Flow that the plugin will have affect upon
     * This action can take place **ONLY** after you save you configuration for the first time.
   * Info Message-Cert: The message that will presented to the user if no Certificate is available
   * Return parameter: The query parameter name used to redirect the user to the end Service after Enrollment 
   * Redirect URL: A URL to redirect to after presented the Info View


## Compatibility matrix

This table matches the Plugin version with the supported COmanage version.

| Plugin |  COmanage |    PHP    |
|:------:|:---------:|:---------:|
| v0.1.0 | v3.1.x    | &gt;=v5.6 |

## License

Licensed under the Apache 2.0 license, for details see [LICENSE](https://github.com/rciam/comanage-registry-plugin-RciamEnroller/blob/master/LICENSE).
