cmake_minimum_required(VERSION 3.4.3)
project(rete C CXX)

# Find PkgConfig to use pkg_check_modules
find_package(PkgConfig REQUIRED)

# Use PkgConfig to find Graphviz libraries and include directories
pkg_check_modules(GRAPHVIZ REQUIRED libgvc cgraph)

if(GRAPHVIZ_FOUND)
    message(STATUS "Found Graphviz include dirs: ${GRAPHVIZ_INCLUDE_DIRS}")
    message(STATUS "Found Graphviz library dirs: ${GRAPHVIZ_LIBRARY_DIRS}") # Still useful to see
    message(STATUS "Graphviz library names: ${GRAPHVIZ_LIBRARIES}")      # Raw names like gvc;cgraph
    message(STATUS "Graphviz link libraries (flags): ${GRAPHVIZ_LINK_LIBRARIES}") # Full flags like -L/path -lgvc

    target_include_directories(rete0 PRIVATE ${GRAPHVIZ_INCLUDE_DIRS})
    target_include_directories(rete1 PRIVATE ${GRAPHVIZ_INCLUDE_DIRS})
    # link_directories(${GRAPHVIZ_LIBRARY_DIRS}) # This line is removed
else()
    message(FATAL_ERROR "Graphviz not found by pkg-config. Please check PKG_CONFIG_PATH or install Graphviz development packages.")
endif()

set(CMAKE_CXX_FLAGS "-fsanitize=address,undefined -g -O0")

add_executable(rete0 rete0.cpp)
# Link against Graphviz libraries using linker flags from pkg-config
target_link_libraries(rete0 PRIVATE ${GRAPHVIZ_LINK_LIBRARIES})
set_target_properties(rete0 PROPERTIES CXX_STANDARD 14)


add_executable(rete1 rete1.cpp)
# Link against Graphviz libraries using linker flags from pkg-config
target_link_libraries(rete1 PRIVATE ${GRAPHVIZ_LINK_LIBRARIES})
set_target_properties(rete1 PROPERTIES CXX_STANDARD 14)
