# Copyright (c) HashiCorp, Inc.
# SPDX-License-Identifier: MPL-2.0

# ********** PROVIDER SETUP **********

terraform {
  required_providers {
    # provider docs https://registry.terraform.io/providers/hashicorp/azurerm/latest/docs/resources/kubernetes_cluster.html
    azurerm = {
      source  = "hashicorp/azurerm"
      version = "~> 3.0"
    }
  }
}

provider "azurerm" {
  features {}
}


# ********** RESOURCES **********

# Defining a resource group is mandatory to create an AKS cluster.
resource "azurerm_resource_group" "default" {
  name     = var.project_name
  location = var.location
}

# The AKS cluster resource
resource "azurerm_kubernetes_cluster" "default" {
  name                = var.project_name
  location            = azurerm_resource_group.default.location
  resource_group_name = azurerm_resource_group.default.name
  dns_prefix          = var.project_name

  default_node_pool {
    name       = "pool0"
    vm_size    = "Standard_A2_v2" # Type of virtual machines to be used

    # The configuration below is about cluster autoscaling
    enable_auto_scaling = true
    type = "VirtualMachineScaleSets"
    min_count = 1
    max_count = 5
    # node_count = 1 # initial node_count https://registry.terraform.io/providers/hashicorp/azurerm/latest/docs/resources/kubernetes_cluster#node_count
  }

  identity {
    type = "SystemAssigned"
  }
}

# This resources creates a local file which corresponds to the cluster's kubeconfig file.
# Allowing to use kubectl in order to manage the Kubernetes cluster from the local machine.
resource "local_file" "kubeconfig" {
  content = azurerm_kubernetes_cluster.default.kube_config_raw
  filename = "${path.root}/outputs/kubeconfig"
}


# ********** OUTPUTS **********

# This output allows to get the path of the generated kubeconfig file through the terraform cli 
output "kubeconfig_path" {
  value = abspath("${path.root}/outputs/kubeconfig")
}
