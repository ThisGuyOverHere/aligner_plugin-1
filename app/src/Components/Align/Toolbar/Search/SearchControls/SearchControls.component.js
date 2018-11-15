import React, {Component} from 'react';
import PropTypes from "prop-types";
import { Icon } from 'semantic-ui-react'

class SearchControlsComponent extends Component {
    static propTypes = {
        featuredSearchResult: PropTypes.number,
        occurrencesList: PropTypes.array,
        setFeatured: PropTypes.func,
    };

    constructor(props) {
        super(props);
        this.state = {
            featuredSearchResult: 0,
            searchResults: [],
        };
    }


    componentDidMount() {
    }

    componentWillUnmount() {

    }

    static getDerivedStateFromProps = (props, state) => {
        state.featuredSearchResult = props.featuredSearchResult;
        state.occurrencesList = props.occurrencesList;
        return state;
    };

    render() {
        return (
            <div id="controls">
                <Icon className={"increment"} name='chevron up'/>
                <Icon className={"decrement"} name='chevron down'/>
                <Icon className={"close"} name='window close outline'/>
            </div>
        );
    }


}

export default SearchControlsComponent;
