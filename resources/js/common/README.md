Anything may import anything from "@/common".

However, "@/common" should not import from anything external to "@/common" other than "@/utils".
Doing so will inevitably cause a circular dependency.
