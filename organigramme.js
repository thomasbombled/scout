document.addEventListener("DOMContentLoaded", () => {
  fetch("organigramme.json")
    .then((response) => response.json())
    .then((data) => {
      renderOrganigramme(data);
    })
    .catch((error) => console.error("Error loading JSON:", error));
});

function renderOrganigramme(data) {
  const container = document.getElementById("organigramme-container");

  // Header
  const header = document.createElement("div");
  header.className = "header";
  header.textContent = data.header;
  container.appendChild(header);

  // Layout container for the top part
  const topLayout = document.createElement("div");
  topLayout.className = "layout-top";
  container.appendChild(topLayout);

  // Sidebar Left
  const sidebarLeft = document.createElement("div");
  sidebarLeft.className = "sidebar-left";
  data.sidebar_left.forEach((item) => {
    const box = document.createElement("div");
    box.className = "box-blue";
    box.textContent = item;
    sidebarLeft.appendChild(box);
  });
  topLayout.appendChild(sidebarLeft);

  // Center Section
  const centerSection = document.createElement("div");
  centerSection.className = "center-section";

  const mainBox = document.createElement("div");
  mainBox.className = "box-gray";
  mainBox.textContent = data.main_tree.label;
  centerSection.appendChild(mainBox);

  const connector1 = document.createElement("div");
  connector1.className = "connector-vertical";
  centerSection.appendChild(connector1);

  const subBox = document.createElement("div");
  subBox.className = "box-gray";
  const subLabel = document.createElement("div");
  subLabel.textContent = data.main_tree.sub.label;
  subBox.appendChild(subLabel);
  const subName = document.createElement("div");
  subName.className = "name";
  subName.textContent = data.main_tree.sub.name;
  subBox.appendChild(subName);
  centerSection.appendChild(subBox);

  const connector2 = document.createElement("div");
  connector2.className = "connector-vertical";
  centerSection.appendChild(connector2);

  topLayout.appendChild(centerSection);

  // Sidebar Right
  const sidebarRight = document.createElement("div");
  sidebarRight.className = "sidebar-right";

  const governanceBox = document.createElement("div");
  governanceBox.className = "box-light-blue";
  governanceBox.textContent = data.sidebar_right.title;
  sidebarRight.appendChild(governanceBox);

  const arrow = document.createElement("div");
  arrow.className = "arrow-down";
  arrow.textContent = "▼";
  sidebarRight.appendChild(arrow);

  data.sidebar_right.items.forEach((item) => {
    const box = document.createElement("div");
    box.className = "box-light-blue";
    box.textContent = item;
    sidebarRight.appendChild(box);
  });
  topLayout.appendChild(sidebarRight);

  // Horizontal line for branches
  const horizontalLineWrapper = document.createElement("div");
  horizontalLineWrapper.className = "connector-horizontal-wrapper";
  const horizontalLine = document.createElement("div");
  horizontalLine.className = "connector-horizontal";
  horizontalLineWrapper.appendChild(horizontalLine);
  container.appendChild(horizontalLineWrapper);

  // Bottom Tree
  const bottomTree = document.createElement("div");
  bottomTree.className = "bottom-tree";
  const children = data.main_tree.sub.children;
  children.forEach((child) => {
    const childContainer = document.createElement("div");
    childContainer.className = "child-box";

    const connectorV = document.createElement("div");
    connectorV.className = "connector-v";
    childContainer.appendChild(connectorV);

    const deptBox = document.createElement("div");
    deptBox.className = "department-box";
    deptBox.style.backgroundColor = child.color;
    const deptName = document.createElement("div");
    deptName.textContent = child.department;
    deptBox.appendChild(deptName);
    const managerName = document.createElement("div");
    managerName.className = "manager";
    managerName.textContent = child.manager;
    deptBox.appendChild(managerName);
    childContainer.appendChild(deptBox);

    bottomTree.appendChild(childContainer);
  });
  container.appendChild(bottomTree);

  // Adjust horizontal line width to span from first child box center to last child box center
  if (children.length > 1) {
    const firstChild = bottomTree.firstChild;
    const lastChild = bottomTree.lastChild;
    const totalWidth = lastChild.offsetLeft - firstChild.offsetLeft;
    horizontalLine.style.width = `${totalWidth}px`;
  } else {
    horizontalLine.style.width = "0px";
  }
}
