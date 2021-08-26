# yaml-migrations

A library to facilitate migrations for YAML configuration files

## Run migrations

Standalone:

```bash
bin/yaml-migrate process -c config.yaml -v
```

Run a single file forcibly, convenient for testing: 

```
bin/yaml-migrate process -c config.yaml -v -f m_replace.yaml
```

Run it in the context of a Bolt installation: 

```
vendor/bobdenotter/yaml-migrations/bin/yaml-migrate process -c vendor/bolt/core/yaml-migrations/config.yaml -v
````


Tip: Reset the checkpoint: 

```bash
echo '1.0.0' > sample/migrations/checkpoint.txt
```
