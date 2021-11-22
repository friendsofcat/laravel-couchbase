#!/bin/bash

# TODO: move step 0 to laravel-test-runner container
## Step 0 - Because we don't know how to access binary from the couchbase container in github action service
echo "Install couchbase required tools for cli usage below"
curl -O https://packages.couchbase.com/releases/couchbase-release/couchbase-release-1.0-amd64.deb
dpkg -i ./couchbase-release-1.0-amd64.deb
apt-get update
apt-get install -y couchbase-server=6.6.*
export PATH=$PATH:/opt/couchbase/bin


echo "couchbase-cli version: $(couchbase-cli --version)"

# Create cluster
echo "Creating couchbase cluster"
couchbase-cli cluster-init -c "$host:$port" \
          --cluster-username $username \
          --cluster-password $password \
          --services data,index,query,analytics \
          --cluster-ramsize 2048 \
          --cluster-index-ramsize 256

# Add new bucket
# Can also be done using BucketCreate command from friendsofcat/laravel-couchbase

echo "Creating couchbase bucket $bucket"
couchbase-cli bucket-create -c "$host:$port" \
        --username $username \
        --password $password \
        --bucket $bucket \
        --bucket-type couchbase \
        --bucket-ramsize 1024 \
        --enable-flush 1


echo "CBQ version: $(cbq --version)"

# Create index to allow n1ql queries
echo "Creating couchbase primary index on $bucket"
cbq -u $username -p $password -e "$host:$port" -s "CREATE PRIMARY INDEX "${bucket}_index" ON $bucket"
