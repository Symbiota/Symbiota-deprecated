# (1) Update schema patch table, remove old version and add current version.

# Different implementations are possible, e.g. we could test that the current schema is 
# the schema that this patch updates from, and only apply the patch in that case.
# Delete all, then insert new row is current expectation of OmOccurrences.checkSchema().

DELETE FROM schemaversion;
INSERT INTO schemaversion (versionnumber) values ('0.9.1.13');

# (2) Put schema changes here.
