cmake_minimum_required(VERSION 3.4.3)
project(rete C CXX)

# Find PkgConfig to use pkg_check_modules
find_package(PkgConfig REQUIRED)

# Use PkgConfig to find Graphviz libraries and include directories
pkg_check_modules(GRAPHVIZ REQUIRED libgvc cgraph)

# Output the found paths for verification
message(STATUS "Found Graphviz include dirs: ${GRAPHVIZ_INCLUDE_DIRS}")
message(STATUS "Found Graphviz libraries: ${GRAPHVIZ_LIBRARIES}")

set(CMAKE_CXX_FLAGS "-fsanitize=address,undefined -g -O0")

add_executable(rete0 rete0.cpp)
# Add Graphviz include directories
target_include_directories(rete0 PRIVATE ${GRAPHVIZ_INCLUDE_DIRS})
# Link against Graphviz libraries
target_link_libraries(rete0 PRIVATE ${GRAPHVIZ_LIBRARIES})
set_target_properties(rete0 PROPERTIES CXX_STANDARD 14)


add_executable(rete1 rete1.cpp)
# Add Graphviz include directories
target_include_directories(rete1 PRIVATE ${GRAPHVIZ_INCLUDE_DIRS})
# Link against Graphviz libraries
target_link_libraries(rete1 PRIVATE ${GRAPHVIZ_LIBRARIES})
set_target_properties(rete1 PROPERTIES CXX_STANDARD 14)
