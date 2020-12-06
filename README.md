# yaml-migrations

A library to facilitate migrations for YAML configuration files

## Run migrations

```bash
bin/yaml-migrate process -c config.yaml -v
```

Tip: Reset the checkpoint: 

```bash
echo '1.0.0' > sample/migrations/checkpoint.txt
```