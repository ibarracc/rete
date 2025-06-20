# RETE

This is a reference tutorial implementation of the rete pattern matching
algorithm, along with GraphViz code to visualize the algorithm as it executes.
The implementation is from
[Robert B. Doorenbos' PhD Thesis: Production Matching for Large Learning Systems](http://reports-archive.adm.cs.cmu.edu/anon/1995/CMU-CS-95-113.pdf).

- `rete0.cpp` is a *faithful* implementation from the thesis, to the extent
   that it has *page number* markings in the source code to refer to the
   thesis. It is recommended to read `rete0.cpp` when one is reading the
   thesis alongside it.

- `rete1.cpp` is a *re-implementation* of `rete0` with no added feature,
   which (in my opinion) irons out some of the quirks of the 
   `rete0` presentation. For one, we don't use a 
   common `ReteNode`. I found the `ReteNode` more confusing
   than enlightening. We also abandon the `left/right` convention, and simply
   speak of `alpha-side/beta-side`.


## Building and Running the Code

This project uses CMake to build the code. You will also need Graphviz to visualize the Rete network.

The build process uses `pkg-config` to locate Graphviz. Ensure you have `pkg-config` installed on your system (e.g., `sudo apt-get install pkg-config` on Debian/Ubuntu, or `brew install pkg-config` on macOS).

### Dependencies

#### Graphviz
Graphviz is used to generate visual representations of the Rete network.

-   **Debian/Ubuntu:**
    ```bash
    sudo apt-get install -y graphviz libgraphviz-dev
    ```
-   **Other Systems:**
    Use your system's package manager to install Graphviz and its development libraries. For example, on macOS with Homebrew, you would typically run:
    ```bash
    brew install graphviz
    ```
    This command should also install the necessary development files (headers and libraries). If you encounter issues during compilation related to missing Graphviz headers, ensure that Graphviz's include and library paths are correctly picked up by CMake, or consult Homebrew's documentation for troubleshooting linking against `graphviz`.

### Compilation Steps

CMake will attempt to automatically locate your Graphviz installation using `pkg-config`. If Graphviz is installed in a non-standard location, you might need to help CMake find it.

Follow the standard CMake workflow to build the project:

1.  Create a build directory:
    ```bash
    mkdir build
    ```
2.  Navigate into the build directory:
    ```bash
    cd build
    ```
3.  Run CMake to configure the project:
    ```bash
    cmake ..
    ```
4.  Compile the code:
    ```bash
    make
    ```

### Troubleshooting Graphviz Detection

If CMake fails to find Graphviz (you'll likely see an error during the `cmake ..` step, possibly mentioning that `GRAPHVIZ_LIBRARIES` or `GRAPHVIZ_INCLUDE_DIRS` could not be found), it means `pkg-config` could not locate the necessary Graphviz `.pc` files (e.g., `libcgraph.pc`, `libgvc.pc`).

To resolve this, you might need to set or modify the `PKG_CONFIG_PATH` environment variable to include the directory containing these `.pc` files. The exact path depends on your specific Graphviz installation method and location. For example, if Graphviz was installed to `/opt/graphviz`, and its `.pc` files are in `/opt/graphviz/lib/pkgconfig`, you would run the following before the `cmake ..` command:

```bash
export PKG_CONFIG_PATH="/opt/graphviz/lib/pkgconfig:$PKG_CONFIG_PATH"
# Now, try running cmake again from your build directory
cmake ..
```

Alternatively, for some CMake setups, providing the root of the Graphviz installation via `CMAKE_PREFIX_PATH` might help components that use `find_library` or `find_path`. While our current setup relies directly on `pkg-config`, this can be a more general CMake approach for finding software:

```bash
# Example: Run from your build directory
cmake -DCMAKE_PREFIX_PATH=/opt/graphviz ..
```
Always ensure the provided paths match your actual installation.

### Execution

After successful compilation, the executables `rete0` and `rete1` will be created in the `build` directory.

You can run them from the project's root directory like so:

```bash
./build/rete0
./build/rete1
```

These programs demonstrate the Rete algorithm. They currently print Graphviz DOT format text to standard output. This output can be redirected to a file (e.g., `graph.dot`) and then viewed using a Graphviz tool like `xdot`.

Example:
```bash
./build/rete0 > rete0_graph.dot
xdot rete0_graph.dot

./build/rete1 > rete1_graph.dot
xdot rete1_graph.dot
```

### Example Rete diagrams to learn from:

##### Test1
![test1.png](./website-images/test1.png)
##### Test2
![test2.png](./website-images/test2.png)
##### Test3
![test3.png](./website-images/test3.png)
##### Test5
![test5.png](./website-images/test5.png)
##### Test from paper
![test_from_paper.png](./website-images/test_from_paper.png)

