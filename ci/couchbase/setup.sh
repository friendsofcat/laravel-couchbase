#!/bin/bash

# TODO: move step 0 to laravel-test-runner container
## Step 0 - Because we don't know how to access binary from the couchbase container in github action service
echo "Install couchbase required tools for cli usage below"
curl -O https://packages.couchbase.com/releases/couchbase-release/couchbase-release-1.0-amd64.deb
dpkg -i ./couchbase-release-1.0-amd64.deb
apt-get update
apt-get install -y couchbase-server=6.6.0
export PATH=$PATH:/opt/couchbase/bin


echo "couchbase-cli version: $(couchbase-cli --version)"

# Create cluster
echo "Creating couchbase cluster"
couchbase-cli cluster-init -c "$COUCHBASE_HOST:$COUCHBASE_PORT" \
          --cluster-username $COUCHBASE_USERNAME \
          --cluster-password $COUCHBASE_PASSWORD \
          --services data,index,query,analytics \
          --cluster-ramsize 2048 \
          --cluster-index-ramsize 256

# Add new bucket
# Can also be done using BucketCreate command from friendsofcat/laravel-couchbase

echo "Creating couchbase bucket $COUCHBASE_BUCKET"
couchbase-cli bucket-create -c "$COUCHBASE_HOST:$COUCHBASE_PORT" \
        --username $COUCHBASE_USERNAME \
        --password $COUCHBASE_PASSWORD \
        --bucket $COUCHBASE_BUCKET \
        --bucket-type couchbase \
        --bucket-ramsize 1024 \
        --enable-flush 1


echo "CBQ version: $(cbq --version)"

# Create index to allow n1ql queries
echo "Creating couchbase primary index on $bucket"
cbq -u $COUCHBASE_USERNAME -p $COUCHBASE_PASSWORD -e "http://$COUCHBASE_HOST:$COUCHBASE_PORT" -s "CREATE PRIMARY INDEX "${COUCHBASE_BUCKET}_index" ON $COUCHBASE_BUCKET"
