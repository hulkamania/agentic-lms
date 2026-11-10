#!/bin/bash
set -e

echo "========================================"
echo "  Systemd IP Alias Setup for .101"
echo "========================================"

# 1. Copy the service file to systemd
echo "1. Installing systemd service..."
sudo cp systemd/alias-192.168.254.101.service /etc/systemd/system/

# 2. Reload systemd
echo "2. Reloading systemd..."
sudo systemctl daemon-reload

# 3. Enable and start the service
echo "3. Enabling and starting service..."
sudo systemctl enable --now alias-192.168.254.101.service

# 4. Verify
echo "4. Verifying IP is bound..."
ip addr show lo | grep 192.168.254.101

echo "========================================"
echo "  Done. IP alias is active."
echo "========================================"
