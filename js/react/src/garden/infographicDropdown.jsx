import React from "react";
//import CrumbBuilder from "../common/crumbBuilder.jsx";


class InfographicDropdown extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      isCollapsed: false
    };
  }

  onButtonClicked() {
    this.setState({ isCollapsed: !this.state.isCollapsed });
  }

  render() {
    return (
      <div
        id="infographic-dropdown"
        className="container-fluid d-print-none"
        style={{ position: "relative", backgroundImage: `url(${this.props.clientRoot}/images/garden/natives-bg.jpg)` }}
      >
        
   			<div className="container mx-auto p-4">
          <div className="row" style={{ position: "relative"}}>
						<div className="col">
							<h1
								style={{ fontWeight: "bold", width: "90%" }}>
								Choose native plants for a smart, beautiful and truly Oregon garden
							</h1>
							<h3 className={ "w-90 will-collapse" + (this.state.isCollapsed ? " is-collapsed" : "") }>
								Native plants thrive in Oregon’s unique landscapes and growing
								conditions, making them both beautiful and wise gardening choices.
								Use the tools below to find plants best suited to your tastes and
								your yard.
							</h3>
						</div>

						<div className={ "col col-4 pl-4 will-collapse" + (this.state.isCollapsed ? " is-collapsed" : "")}>
							<h2 style={{ fontWeight: "bold" }}>Why native plants?</h2>
							<h4>They need less water and fewer chemicals when established.</h4>
							<h4>
								They attract native pollinators, birds and other helpful
								creatures.
							</h4>
							<h4>
								They preserve our natural landscape and support a healthy and
								diverse ecosystem.
							</h4>
							<h4>
								They provide critical habitat connections for birds and
								wildlife.
							</h4>
						</div>
						
					<button
						style={{
							position: "absolute",
							bottom: 0,
							right: 0,
							marginRight: "-2.5em",
							marginBottom: "-3.5em"
						}}
						onClick={ this.onButtonClicked.bind(this) }>
						<img
							src={ `${this.props.clientRoot}/images/garden/collapse-arrow.png` }
							className={ "will-v-flip" + (this.state.isCollapsed ? " v-flip" : "") }
							style={{
								width: "4em",
								height: "4em",
								opacity: "0.5"
							}}
							alt="toggle collapse"
						/>
					</button>
						
					</div>

      	</div>
      </div>
    );
  }
}

export default InfographicDropdown;